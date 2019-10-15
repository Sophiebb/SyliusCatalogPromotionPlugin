<?php

declare(strict_types=1);

namespace Setono\SyliusBulkSpecialsPlugin\EventSubscriber;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Setono\SyliusBulkSpecialsPlugin\Handler\ChannelPricingRecalculateHandlerInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;

class ChannelPricingDoctrineEventListener
{
    /** @var ChannelPricingRecalculateHandlerInterface */
    protected $channelPricingRecalculateHandler;

    /** @var array|ChannelPricingInterface[] */
    private $channelPricingsToRecalculate = [];

    public function __construct(ChannelPricingRecalculateHandlerInterface $channelPricingRecalculateHandler)
    {
        $this->channelPricingRecalculateHandler = $channelPricingRecalculateHandler;
    }

    /**
     * On ChannelPricing's originalPrice update
     */
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof ChannelPricingInterface) {
            return;
        }

        if ($args->hasChangedField('originalPrice') && $args->getOldValue('originalPrice') !== $args->getNewValue('originalPrice')) {
            $this->channelPricingsToRecalculate[$entity->getId()] = $entity;
        }
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof ChannelPricingInterface) {
            return;
        }

        if (isset($this->channelPricingsToRecalculate[$entity->getId()])) {
            unset($this->channelPricingsToRecalculate[$entity->getId()]);

            $this->channelPricingRecalculateHandler->handle($entity);
        }
    }
}
