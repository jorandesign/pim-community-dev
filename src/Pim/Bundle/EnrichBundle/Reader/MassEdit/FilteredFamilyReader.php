<?php

namespace Pim\Bundle\EnrichBundle\Reader\MassEdit;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\AbstractConfigurableStepElement;
use Akeneo\Bundle\BatchBundle\Item\ItemReaderInterface;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Akeneo\Component\StorageUtils\Cursor\CursorFactoryInterface;
use Akeneo\Component\StorageUtils\Cursor\CursorInterface;
use Doctrine\ORM\EntityNotFoundException;
use Pim\Bundle\CatalogBundle\Repository\FamilyRepositoryInterface;
use Pim\Bundle\EnrichBundle\Entity\Repository\MassEditRepositoryInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author    Adrien Pétremann <adrien.petremann@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class FilteredFamilyReader extends AbstractConfigurableStepElement implements
    ItemReaderInterface,
    StepExecutionAwareInterface
{
    /** @var StepExecution */
    protected $stepExecution;

    /** @var bool */
    protected $isExecuted = false;

    /** @var CursorInterface */
    protected $families;

    /** @var FamilyRepositoryInterface */
    protected $familyRepository;

    /** @var CursorFactoryInterface */
    protected $cursorFactory;

    public function __construct(
        MassEditRepositoryInterface $massEditRepository,
        FamilyRepositoryInterface $familyRepository,
        CursorFactoryInterface $cursorFactory
    ) {
        $this->massEditRepository = $massEditRepository;
        $this->familyRepository   = $familyRepository;
        $this->cursorFactory      = $cursorFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        $configuration = $this->getJobConfiguration();

        if (!$this->isExecuted) {
            $this->isExecuted = true;
            $this->families = $this->getFamiliesCursor($configuration['filters']);
        }

        $result = $this->families->current();

        if (!empty($result)) {
            $this->stepExecution->incrementSummaryInfo('read');
            $this->families->next();
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFields()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }

    /**
     * @param array $filters
     *
     * @return \Akeneo\Component\StorageUtils\Cursor\CursorInterface
     */
    protected function getFamiliesCursor(array $filters)
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired(['field', 'operator', 'value']);

        // In the case of the Family reader, we only have 1 filter for IDS
        $filter = current($filters);
        $filter = $resolver->resolve($filter);

        $familiesIds = $filter['value'];
        $familyQueryBuilder = $this->familyRepository->createQBFromFamiliesIds($familiesIds);

        $cursor = $this->cursorFactory->createCursor($familyQueryBuilder);

        return $cursor;
    }

    /**
     * Return the job configuration
     *
     * @return array
     *
     * @throws EntityNotFoundException
     */
    protected function getJobConfiguration()
    {
        $jobExecution    = $this->stepExecution->getJobExecution();
        $massEditJobConf = $this->massEditRepository->findOneBy(['jobExecution' => $jobExecution]);

        if (null === $massEditJobConf) {
            throw new EntityNotFoundException(sprintf(
                'No JobConfiguration found for jobExecution with id %s',
                $jobExecution->getId()
            ));
        }

        return json_decode(stripcslashes($massEditJobConf->getConfiguration()), true);
    }
}
