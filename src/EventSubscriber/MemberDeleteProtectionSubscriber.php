<?php

namespace App\EventSubscriber;

use App\Entity\Member;
use App\Service\AuditLogger;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityDeletedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class MemberDeleteProtectionSubscriber
{
    public function __construct(
        private AuditLogger $auditLogger,
    ) {}

    #[AsEventListener(event: BeforeEntityDeletedEvent::class)]
    public function onBeforeEntityDeleted(BeforeEntityDeletedEvent $event): void
    {
        $entity = $event->getEntityInstance();

        if (!$entity instanceof Member) {
            return;
        }

        $this->auditLogger->memberDeleted($entity);
    }
}
