<?php
/**
 * @author Manuel Aguirre
 */

namespace Optime\Email\Bundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Optime\Email\Bundle\Repository\EmailLogRepository;
use Optime\Email\Bundle\Service\Email\EmailRecipientInterface;
use Optime\Email\Bundle\Service\Email\TemplateData;
use Optime\Util\Entity\Traits\DatesTrait;
use Optime\Util\Entity\Traits\ExternalUuidTrait;
use Symfony\Component\Mime\Email;
use Throwable;
use Traversable;
use function get_resource_id;
use function is_iterable;
use function is_object;
use function is_resource;
use function iterator_to_array;

#[ORM\Table('emails_bundle_email_log')]
#[ORM\Entity(EmailLogRepository::class)]
class EmailLog
{
    use ExternalUuidTrait, DatesTrait;

    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private readonly ?int $id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'email_template_id')]
    private ?EmailTemplate $template;

    #[ORM\Column]
    private string $locale;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $subject;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $content;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $variables;

    #[ORM\Column]
    private string $recipient;

    #[ORM\Column]
    private string $emailCode;

    #[ORM\Column]
    private string $recipientIdentifier;

    #[ORM\Column(nullable: true)]
    private ?string $sessionUserIdentifier;

    #[ORM\Column(type: 'string', enumType: EmailLogStatus::class, nullable: true)]
    private EmailLogStatus $status;

    #[ORM\Column(nullable: true)]
    private ?string $failureMessage;

    public function __construct(
        string $locale,
        TemplateData $templateData,
        EmailRecipientInterface $recipient,
        array $templateVars,
        ?string $sessionUserIdentifier,
        ?string $info = null
    ) {
        $this->locale = $locale;
        $this->emailCode = $templateData->getEmailCode();
        $this->recipient = $recipient->getEmail();
        $this->recipientIdentifier = $recipient->getRecipientId();
        $this->variables = $this->normalizeVars($templateVars);
        $this->sessionUserIdentifier = $sessionUserIdentifier;
        $this->template = $templateData->getTemplate();
        $this->status = $this->template ? EmailLogStatus::pending : EmailLogStatus::no_template;

        if (null !== $info) {
            $this->failureMessage = $info;
        }
    }

    public static function create(
        string $locale,
        TemplateData $templateData,
        EmailRecipientInterface $recipient,
        array $templateVars,
        ?string $sessionUserIdentifier,
    ): self {
        if (null === $templateData->getConfig()) {
            return new self(
                $locale,
                $templateData,
                $recipient,
                $templateVars,
                $sessionUserIdentifier,
                'Undefined emailConfig'
            );
        }

        if (null === $templateData->getTemplate()) {
            $app = $templateData->getApp();
            return new self(
                $locale,
                $templateData,
                $recipient,
                $templateVars,
                $sessionUserIdentifier,
                $app
                    ? 'Undefined EmailTemplate for App#' . $app->getId()
                    : 'Undefined EmailTemplate',
            );
        }

        return new self(
            $locale,
            $templateData,
            $recipient,
            $templateVars,
            $sessionUserIdentifier,
        );
    }

    public function addError(Throwable $error): void
    {
        $this->status = EmailLogStatus::error;
        $this->failureMessage = $error->getMessage();
    }

    public function setEmail(Email $email): void
    {
        $this->subject = $email->getSubject();
        $this->content = $email->getHtmlBody();
    }

    public function confirmSend(): void
    {
        $this->status = EmailLogStatus::send;
    }

    private function normalizeVars(array $vars): array
    {
        foreach ($vars as $index => &$var) {
            if (is_object($var)) {
                $var = '(object) ' . $var::class;
            } elseif (is_resource($var)) {
                $var = '(resource) ' . get_resource_id($var);
            } elseif (is_iterable($var)) {
                if ($var instanceof Traversable) {
                    $var = iterator_to_array($var);
                }

                $var = $this->normalizeVars($var);
            }
        };

        return $vars;
    }
}