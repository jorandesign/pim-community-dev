<?php

namespace Pim\Bundle\DataGridBundle\Controller;

use Pim\Bundle\BaseConnectorBundle\JobLauncher\SimpleJobLauncher;
use Pim\Bundle\CatalogBundle\Context\CatalogContext;
use Pim\Bundle\DataGridBundle\Adapter\GridFilterAdapterInterface;
use Pim\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Pim\Bundle\ImportExportBundle\Entity\Repository\JobInstanceRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Products quick export
 *
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductExportController extends ExportController
{
//    /** @var Request $request */
//    protected $request;
//
//    /** @var MassActionDispatcher $massActionDispatcher */
//    protected $massActionDispatcher;

    /** @var SerializerInterface $serializer */
    protected $serializer;

    /** @var GridFilterAdapterInterface */
    protected $gridFilterAdapter;

    /** @var JobInstanceRepository */
    protected $jobInstanceRepository;

    /** @var SecurityContextInterface */
    protected $securityContext;

    /** @var SimpleJobLauncher */
    protected $jobLauncher;

    /** @var CatalogContext */
    protected $catalogContext;

    /**
     * @param Request                    $request
     * @param MassActionDispatcher       $massActionDispatcher
     * @param SerializerInterface        $serializer
     * @param GridFilterAdapterInterface $gridFilterAdapter
     * @param JobInstanceRepository      $jobInstanceRepository
     * @param SecurityContextInterface   $securityContext
     * @param SimpleJobLauncher          $jobLauncher
     * @param CatalogContext             $catalogContext
     */
    public function __construct(
        Request $request,
        MassActionDispatcher $massActionDispatcher,
        SerializerInterface $serializer,
        GridFilterAdapterInterface $gridFilterAdapter,
        JobInstanceRepository $jobInstanceRepository,
        SecurityContextInterface $securityContext,
        SimpleJobLauncher $jobLauncher,
        CatalogContext $catalogContext
    ) {
        parent::__construct($request, $massActionDispatcher, $serializer);

//        $this->request               = $request;
//        $this->massActionDispatcher  = $massActionDispatcher;
        $this->serializer            = $serializer;
        $this->gridFilterAdapter     = $gridFilterAdapter;
        $this->jobInstanceRepository = $jobInstanceRepository;
        $this->securityContext       = $securityContext;
        $this->jobLauncher           = $jobLauncher;
        $this->catalogContext        = $catalogContext;
    }
//
//    public function indexAction()
//    {
//        $rawConfiguration = json_encode($this->gridFilterAdapter->adapt($this->request));
//        $jobInstance      = $this->jobInstanceRepository->findOneBy(['code' => 'product_quick_export_csv']);
//
//        $jobExecution = $this->jobLauncher->launch($jobInstance, $this->getUser(), $rawConfiguration);
//
//        return true;
//    }

    /**
     * {@inheritdoc}
     */
    protected function createFilename()
    {
        $dateTime = new \DateTime();
        return sprintf(
            'products_export_%s_%s_%s.%s',
            $this->catalogContext->getLocaleCode(),
            $this->catalogContext->getScopeCode(),
            $dateTime->format('Y-m-d_H-i-s'),
            $this->getFormat()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function quickExport()
    {
        $rawConfiguration = json_encode($this->gridFilterAdapter->adapt($this->request));
        $jobInstance      = $this->jobInstanceRepository->findOneBy(['code' => 'product_quick_export_csv']);

        $jobExecution = $this->jobLauncher->launch($jobInstance, $this->getUser(), $rawConfiguration);
    }

    /**
     * Get a user from the Security Context
     *
     * @return \Symfony\Component\Security\Core\User\UserInterface|null
     *
     * @see Symfony\Component\Security\Core\Authentication\Token\TokenInterface::getUser()
     */
    protected function getUser()
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
