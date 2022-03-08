<?php
/**
 * @author Manuel Aguirre
 */

namespace Optime\Email\Bundle\Service\Email;

use Doctrine\ORM\EntityManagerInterface;
use Optime\Email\Bundle\Entity\EmailApp;
use Optime\Email\Bundle\Repository\EmailAppRepository;
use Optime\Email\Bundle\Repository\EmailMasterRepository;
use Optime\Email\Bundle\Repository\EmailTemplateRepository;
use Symfony\Component\Security\Core\Security;

/**
 * @author Manuel Aguirre
 */
class MailerFactory
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ?Security $security,
        private Mailer $mailer,
        private EmailMasterRepository $masterRepository,
        private EmailTemplateRepository $templateRepository,
        private EmailAppRepository $appRepository,
    ) {
    }

    public function create(
        string $emailCode,
        ?EmailApp $app,
    ): MailerIntent {
        $config = $this->masterRepository->byCode($emailCode);

        if (!$config) {
            return $this->createMailerIntent(
                new TemplateData($emailCode, null, null, $app)
            );
        }

        if (null === $app) {
            // revisamos a ver si hay una app por efecto
            $app = $this->appRepository->findDefaultIfApply();

            if (null === $app) {
                return $this->createMailerIntent(
                    new TemplateData($emailCode, $config, null, null)
                );
            }
        }

        return $this->createMailerIntent(new TemplateData(
            $emailCode,
            $config,
            $this->templateRepository->byConfigAndApp($config, $app),
            $app
        ));
    }

    private function createMailerIntent(TemplateData $templateData): MailerIntent
    {
        return new MailerIntent(
            $this->entityManager,
            $this->security,
            $this->mailer,
            $templateData,
        );
    }
}