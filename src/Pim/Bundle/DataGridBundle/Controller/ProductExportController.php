<?php

namespace Pim\Bundle\DataGridBundle\Controller;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Akeneo\Component\StorageUtils\Saver\SaverInterface;
use Pim\Bundle\DataGridBundle\Adapter\GridFilterAdapterInterface;
use Pim\Bundle\EnrichBundle\Factory\MassEditJobConfigurationFactory;
use Pim\Bundle\ImportExportBundle\Entity\Repository\JobInstanceRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * Products quick export
 *
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductExportController
{
    /** @var Request */
    protected $request;

    /** @var GridFilterAdapterInterface */
    protected $gridFilterAdapter;

    /** @var SaverInterface */
    protected $jobConfigSaver;

    /** @var MassEditJobConfigurationFactory */
    protected $jobConfigFactory;

    /** @var JobInstanceRepository */
    protected $jobInstanceRepository;

    /** @var SecurityContextInterface */
    protected $securityContext;

    /**
     * @param Request                         $request
     * @param GridFilterAdapterInterface      $gridFilterAdapter
     * @param MassEditJobConfigurationFactory $jobConfigFactory
     * @param SaverInterface                  $jobConfigSaver
     * @param JobInstanceRepository           $jobInstanceRepository
     * @param SecurityContextInterface        $securityContext
     */
    public function __construct(
        Request $request,
        GridFilterAdapterInterface $gridFilterAdapter,
        MassEditJobConfigurationFactory $jobConfigFactory,
        SaverInterface $jobConfigSaver,
        JobInstanceRepository $jobInstanceRepository,
        SecurityContextInterface $securityContext
    ) {
        $this->request               = $request;
        $this->gridFilterAdapter     = $gridFilterAdapter;
        $this->jobConfigFactory      = $jobConfigFactory;
        $this->jobConfigSaver        = $jobConfigSaver;
        $this->jobInstanceRepository = $jobInstanceRepository;
        $this->securityContext       = $securityContext;
    }

    /**
     * Index action
     */
    public function indexAction()
    {
        $rawConfiguration = json_encode($this->gridFilterAdapter->adapt($this->request));
        $jobExecution = new JobExecution();
        $massEditConf = $this->jobConfigFactory->create($jobExecution, $rawConfiguration);

        $jobInstance = $this->jobInstanceRepository->findOneBy(['code' => 'product_quick_export_csv']);

//        $this->jobConfigSaver->save($massEditConf);

//        $this->simpleJobLauncher->launch($jobInstance, $this->getUser(), $rawConfiguration, $jobExecution);

    }

    /**
     * Get a user from the Security Context
     *
     * @return \Symfony\Component\Security\Core\User\UserInterface|null
     *
     * @see Symfony\Component\Security\Core\Authentication\Token\TokenInterface::getUser()
     */
    public function getUser()
    {
        if (null === $token = $this->securityContext->getToken()) {
            return null;
        }

        if (!is_object($user = $token->getUser())) {
            return null;
        }

        return $user;
    }
}
