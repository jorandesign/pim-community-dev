<?php

namespace Pim\Bundle\EnrichBundle\Controller;

use Pim\Bundle\CatalogBundle\Repository\ChannelRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Channel controller
 *
 * @author    Filips Alpe <filips@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChannelRestController
{
    /** @var ChannelRepositoryInterface */
    protected $channelRepository;

    /** @var NormalizerInterface */
    protected $normalizer;

    /**
     * @param ChannelRepositoryInterface $channelRepository
     * @param NormalizerInterface        $normalizer
     */
    public function __construct(
        ChannelRepositoryInterface $channelRepository,
        NormalizerInterface $normalizer
    ) {
        $this->channelRepository = $channelRepository;
        $this->normalizer        = $normalizer;
    }

    /**
     * @return JsonResponse
     */
    public function indexAction()
    {
        $channels = $this->channelRepository->findAll();

        $normalizedChannels = $this->normalizer->normalize($channels, 'json');

        return new JsonResponse($normalizedChannels);
    }

    /**
     * @param int $id
     *
     * @return JsonResponse
     */
    public function getAction($id)
    {
        $channel = $this->channelRepository->findOneById($id);

        return new JsonResponse($this->normalizer->normalize($channel, 'json'));
    }
}