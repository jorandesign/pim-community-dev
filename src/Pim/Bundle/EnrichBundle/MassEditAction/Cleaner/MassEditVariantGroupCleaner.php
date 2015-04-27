<?php

namespace Pim\Bundle\EnrichBundle\MassEditAction\Cleaner;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\AbstractConfigurableStepElement;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Akeneo\Bundle\StorageUtilsBundle\Repository\IdentifiableObjectRepositoryInterface;
use Akeneo\Component\StorageUtils\Cursor\PaginatorFactoryInterface;
use Akeneo\Component\StorageUtils\Detacher\ObjectDetacherInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Pim\Bundle\CatalogBundle\Model\ProductInterface;
use Pim\Bundle\CatalogBundle\Query\ProductQueryBuilderFactoryInterface;
use Pim\Bundle\CatalogBundle\Query\ProductQueryBuilderInterface;
use Pim\Bundle\EnrichBundle\Entity\Repository\MassEditRepositoryInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\ValidatorInterface;

/**
 * TODO: make better description
 * Custom step for mass edit of variant group. As we need to check on every products if there is already an axis
 *
 * @author    Olivier Soulet <olivier.soulet@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MassEditVariantGroupCleaner extends AbstractConfigurableStepElement implements StepExecutionAwareInterface
{
    /** @var StepExecution */
    protected $stepExecution;

    /** @var MassEditRepositoryInterface */
    protected $massEditRepository;

    /** @var ObjectManager */
    protected $objectManager;

    /** @var ProductQueryBuilderFactoryInterface */
    protected $pqbFactory;

    /** @var PaginatorFactoryInterface */
    protected $paginatorFactory;

    /** @var ObjectDetacherInterface */
    protected $objectDetacher;

    /**  @var IdentifiableObjectRepositoryInterface */
    protected $groupRepository;

    /**
     * @param ProductQueryBuilderFactoryInterface   $pqbFactory
     * @param PaginatorFactoryInterface             $paginatorFactory
     * @param ObjectDetacherInterface               $objectDetacher
     * @param MassEditRepositoryInterface           $massEditRepository
     * @param ObjectManager                         $objectManager
     * @param IdentifiableObjectRepositoryInterface $groupRepository
     */
    public function __construct(
        ProductQueryBuilderFactoryInterface $pqbFactory,
        PaginatorFactoryInterface $paginatorFactory,
        ObjectDetacherInterface $objectDetacher,
        MassEditRepositoryInterface $massEditRepository,
        ObjectManager $objectManager,
        IdentifiableObjectRepositoryInterface $groupRepository
    ) {
        $this->pqbFactory         = $pqbFactory;
        $this->paginatorFactory   = $paginatorFactory;
        $this->objectDetacher     = $objectDetacher;
        $this->massEditRepository = $massEditRepository;
        $this->objectManager      = $objectManager;
        $this->groupRepository    = $groupRepository;
    }

    /**
     * @param array $configuration
     */
    public function execute(array $configuration)
    {
        $actions = $configuration['actions'];

        $variantGroup = $actions[0]['value'];

        var_dump($variantGroup);

        $variantGroup = $this->groupRepository->findOneByIdentifier($variantGroup);
        $axisAttributes = $variantGroup->getAxisAttributes();

        $default = [];
        foreach ($axisAttributes as $axisAttribute) {
            $default[$axisAttribute->getCode()] = null;
        }

        var_dump($default);

        $cursor = $this->getProductsCursor($configuration['filters']);
        $paginator = $this->paginatorFactory->createPaginator($cursor);

        $ids = [];
        foreach ($paginator as $productsPage) {
            foreach ($productsPage as $index => $product) {
                $test = true;
                foreach($product->getAttributes() as $attribute) {
                    if(array_key_exists($attribute->getCode(), $default)) {
                        var_dump($attribute->getCode());
                        $test = false;
                    }
                }
                if ($test) {
                    $ids[] = $product->getId();
                }
            }

        }

        $configuration['filters'] = [['field' => 'id', 'operator' => 'IN', 'value' => $ids]];
        var_dump($configuration);

        $this->setJobConfiguration(json_encode($configuration));
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFields()
    {
        return [];
    }

    /**
     * Return the job configuration
     *
     * @param string $configuration
     */
    protected function setJobConfiguration($configuration)
    {
        $jobExecution    = $this->stepExecution->getJobExecution();
        $massEditJobConf = $this->massEditRepository->findOneBy(['jobExecution' => $jobExecution]);
        $massEditJobConf->setConfiguration($configuration);

        $this->objectManager->persist($massEditJobConf);
        $this->objectManager->flush($massEditJobConf);
    }

    /**
     * {@inheritdoc}
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;

        return $this;
    }

    /**
     * @param array $productsPage
     */
    protected function detachProducts(array $productsPage)
    {
        foreach ($productsPage as $product) {
            $this->objectDetacher->detach($product);
        }
    }

    /**
     * @return ProductQueryBuilderInterface
     */
    protected function getProductQueryBuilder()
    {
        return $this->pqbFactory->create();
    }

    /**
     * @param array $filters
     *
     * @return \Akeneo\Component\StorageUtils\Cursor\CursorInterface
     */
    protected function getProductsCursor(array $filters)
    {
        $productQueryBuilder = $this->getProductQueryBuilder();

        $resolver = new OptionsResolver();
        $resolver->setRequired(['field', 'operator', 'value']);
        $resolver->setOptional(['context']);
        $resolver->setDefaults([
            'context' => ['locale' => null, 'scope' => null]
        ]);

        foreach ($filters as $filter) {
            $filter = $resolver->resolve($filter);
            $productQueryBuilder->addFilter(
                $filter['field'],
                $filter['operator'],
                $filter['value'],
                $filter['context']
            );
        }

        return $productQueryBuilder->execute();
    }

    /**
     * @param ConstraintViolationListInterface $violations
     * @param ProductInterface                 $product
     */
    protected function addWarningMessage($violations, $product)
    {
        foreach ($violations as $violation) {
            // TODO re-format the message, property path doesn't exist for class constraint
            // for instance cf VariantGroupAxis
            $invalidValue = $violation->getInvalidValue();
            if (is_object($invalidValue) && method_exists($invalidValue, '__toString')) {
                $invalidValue = (string) $invalidValue;
            } elseif (is_object($invalidValue)) {
                $invalidValue = get_class($invalidValue);
            }
            $errors = sprintf(
                "%s: %s: %s\n",
                $violation->getPropertyPath(),
                $violation->getMessage(),
                $invalidValue
            );
            $this->stepExecution->addWarning($this->getName(), $errors, [], $product);
        }
    }
}
