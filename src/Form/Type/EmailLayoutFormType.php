<?php
/**
 * @author Manuel Aguirre
 */

namespace Optime\Email\Bundle\Form\Type;

use Optime\Email\Bundle\Constraints\TwigContent;
use Optime\Email\Bundle\Entity\EmailLayout;
use Optime\Util\Form\Type\AutoTransFieldType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @author Manuel Aguirre
 */
class EmailLayoutFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('description', TextareaType::class);
        $builder->add('content', AutoTransFieldType::class, [
            'type' => TextareaType::class,
            'entry_constraints' => [new NotBlank(), new TwigContent()],
            'auto_save' => true,
            'item_options' => [
                'attr' => [
                    'rows' => 10,
                    'data-code-mirror' => true,
                ],
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EmailLayout::class,
            'auto_save_translations' => true,
        ]);
    }
}