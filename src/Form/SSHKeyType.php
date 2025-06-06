<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\SSHKey;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * @extends AbstractType<SSHKey>
 */
class SSHKeyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'ssh_key.label.name',
                'attr' => ['placeholder' => 'ssh_key.placeholder.name'],
                'constraints' => [
                    new NotBlank(['message' => 'ssh_key.name_required']),
                ],
            ])
            ->add('publicKey', TextareaType::class, [
                'label' => 'ssh_key.label.public_key',
                'attr' => [
                    'placeholder' => 'ssh_key.placeholder.public_key',
                    'rows' => 5,
                ],
                'constraints' => [
                    new NotBlank(['message' => 'ssh_key.public_key_required']),
                    new Regex([
                        'pattern' => '/^ssh-rsa|ssh-ed25519|ecdsa-sha2-nistp256|ecdsa-sha2-nistp384|ecdsa-sha2-nistp521/',
                        'message' => 'ssh_key.public_key_invalid_format',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'data_class' => SSHKey::class,
        ]);
    }
}
