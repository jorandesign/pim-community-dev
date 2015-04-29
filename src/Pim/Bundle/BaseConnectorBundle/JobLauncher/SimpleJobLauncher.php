<?php

namespace Pim\Bundle\BaseConnectorBundle\JobLauncher;

use Akeneo\Bundle\BatchBundle\Entity\JobInstance;
use Akeneo\Bundle\BatchBundle\Job\JobRepositoryInterface;
use Akeneo\Bundle\BatchBundle\Launcher\SimpleJobLauncher as BaseSimpleJobLauncher;
use Akeneo\Component\StorageUtils\Saver\SaverInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Pim\Bundle\EnrichBundle\Factory\MassEditJobConfigurationFactory;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Override of the BatchBundle SimpleJobLauncher to add the JobConfig
 * logic before launching the job.
 *
 * @author    Adrien Pétremann <adrien.petremann@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class SimpleJobLauncher extends BaseSimpleJobLauncher
{
    /** @var SaverInterface */
    protected $jobConfigSaver;

    /** @var MassEditJobConfigurationFactory */
    protected $jobConfigFactory;

    /** @var ObjectManager */
    protected $objectManager;

    /**
     * @param JobRepositoryInterface          $jobRepository
     * @param MassEditJobConfigurationFactory $jobConfigFactory
     * @param ObjectManager                   $objectManager
     * @param string                          $rootDir
     * @param string                          $environment
     */
    public function __construct(
        JobRepositoryInterface $jobRepository,
        MassEditJobConfigurationFactory $jobConfigFactory,
        ObjectManager $objectManager,
        $rootDir,
        $environment
    ) {
        parent::__construct($jobRepository, $rootDir, $environment);

        $this->jobConfigFactory = $jobConfigFactory;
        $this->objectManager    = $objectManager;
    }

    /**
     * {@inheritdoc}
     *
     * We override this method to save the Job configuration which will be retrieved
     * on the execution of the given $jobInstance.
     *
     * Since the BatchBundle uses its own EntityManager, we have to merge the $jobExecution it created.
     */
    public function launch(JobInstance $jobInstance, UserInterface $user, $rawConfiguration = null)
    {
        $jobExecution = $this->createJobExecution($jobInstance, $user);
        $executionId  = $jobExecution->getId();
        $pathFinder   = new PhpExecutableFinder();

        $jobExecution = $this->objectManager->merge($jobExecution);

        $massEditConf = $this->jobConfigFactory->create($jobExecution, $rawConfiguration);

        $this->objectManager->persist($massEditConf);
        $this->objectManager->flush($massEditConf);

        $cmd = sprintf(
            '%s %s/console akeneo:batch:job --env=%s %s %s %s %s >> %s/logs/batch_execute.log 2>&1',
            $pathFinder->find(),
            $this->rootDir,
            $this->environment,
            $this->isConfigTrue('email') ? sprintf('--email="%s"', $user->getEmail()) : '',
            $jobInstance->getCode(),
            $executionId,
            !empty($rawConfiguration) ? sprintf('--config="%s"', $rawConfiguration) : '',
            $this->rootDir
        );
//die($cmd);
        $this->launchInBackground($cmd);

        return $jobExecution;
    }
}
