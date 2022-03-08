<?php
/**
 * @author Manuel Aguirre
 */

namespace Optime\Email\Bundle\Service\Email;

use Doctrine\ORM\EntityManagerInterface;
use Optime\Email\Bundle\Entity\EmailLog;
use Symfony\Component\Security\Core\Security;
use Throwable;
use function method_exists;

/**
 * @author Manuel Aguirre
 */
class MailerIntent
{
    private string|null|false $loggedUserIdentifier = false;
    private Throwable|null $lastError = null;
    private EmailLog|null $lastLog = null;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ?Security $security,
        private Mailer $mailer,
        private TemplateData $templateData,
    ) {
    }

    public function send(
        array $templateVars,
        EmailRecipientInterface $recipient
    ): bool {
        $this->lastLog = $log = EmailLog::create(
            $this->templateData,
            $recipient,
            $this->getLoggedUserIdentifier(),
        );

        try {
            $this->mailer->send(
                $this->templateData->getTemplate(),
                $templateVars,
                $recipient,
                $log
            );

            return true;
        } catch (Throwable $exception) {
            $log->addError($exception);
            $this->lastError = $exception;
        } finally {
            $this->entityManager->persist($log);
            $this->entityManager->flush();
        }

        return false;
    }

    public function getLastError(): ?Throwable
    {
        return $this->lastError;
    }

    public function getLastLog(): ?EmailLog
    {
        return $this->lastLog;
    }

    private function getLoggedUserIdentifier(): ?string
    {
        if (false !== $this->loggedUserIdentifier) {
            return $this->loggedUserIdentifier;
        }

        if (!$this->security || !$this->security->getUser()) {
            return $this->loggedUserIdentifier = null;
        }

        $user = $this->security->getUser();

        return $this->loggedUserIdentifier = method_exists($user, 'getId')
            ? $user->getId()
            : $user->getUserIdentifier();
    }
}