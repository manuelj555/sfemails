<?php
/**
 * @author Manuel Aguirre
 */

namespace Optime\Email\Bundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Optime\Email\Bundle\Entity\EmailTemplate;
use Optime\Email\Bundle\Form\Type\EmailTemplateFormType;
use Optime\Email\Bundle\Repository\EmailTemplateRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @author Manuel Aguirre
 */
#[Route("/config/templates")]
class EmailTemplateController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route("/", name: "optime_emails_template_list")]
    public function index(EmailTemplateRepository $repository): Response
    {
        $items = $repository->findAll();

        return $this->render('@OptimeEmail/email_template/index.html.twig', [
            'items' => $items,
        ]);
    }

    #[Route("/create", name: "optime_emails_template_create")]
    public function create(Request $request): Response
    {
        $template = new EmailTemplate();
        $form = $this->createForm(EmailTemplateFormType::class, $template, [
            'action' => $request->getRequestUri()
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() and $form->isValid()) {
            $this->entityManager->persist($form->getData());
            $this->entityManager->flush();

            return $this->redirectToRoute('optime_emails_template_list');
        }

        return $this->render('@OptimeEmail/email_template/form.html.twig', [
            'form' => $form->createView(),
            'item' => $template,
        ]);
    }

    #[Route("/edit/{uuid}/", name: "optime_emails_template_edit")]
    public function edit(
        Request $request,
        EmailTemplate $template,
    ): Response {
        $form = $this->createForm(EmailTemplateFormType::class, $template);
        $form->handleRequest($request);

        if ($form->isSubmitted() and $form->isValid()) {
            $this->entityManager->persist($template);
            $this->entityManager->flush();

            return $this->redirectToRoute('optime_emails_template_list');
        }

        return $this->render('@OptimeEmail/email_template/form.html.twig', [
            'form' => $form->createView(),
            'item' => $template,
        ]);
    }
}