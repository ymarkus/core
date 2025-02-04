<?php

declare(strict_types=1);

namespace Bolt\Event\Subscriber;

use Bolt\Event\ContentEvent;
use Bolt\Log\LoggerTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class ContentSaveSubscriber implements EventSubscriberInterface
{
    use LoggerTrait;

    public const PRIORITY = 100;

    /** @var TagAwareCacheInterface */
    private $cache;

    public function __construct(TagAwareCacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function postSave(ContentEvent $event): ContentEvent
    {
        // Make sure we flush the cache for the menu's
        $this->cache->invalidateTags(['backendmenu', 'frontendmenu']);

        // Saving an entry in the log.
        $context = [
            'content_id' => $event->getContent()->getId(),
            'content_type' => $event->getContent()->getContentType(),
            'title' => $event->getContent()->getExtras()['title'],
        ];
        $this->logger->info('Saved content "{title}" ({content_type} № {content_id})', $context);

        return $event;
    }

    public static function getSubscribedEvents()
    {
        return [
            ContentEvent::POST_SAVE => ['postSave', self::PRIORITY],
        ];
    }
}
