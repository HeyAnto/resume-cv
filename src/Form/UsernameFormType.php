<?php

namespace App\Form;

use App\Entity\Profile;
use App\Validator\UniqueUsername;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class UsernameFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Username',
                'attr' => [
                    'placeholder' => 'johndoe',
                    'required' => true,
                    'data-max' => '255',
                    'autocomplete' => 'off'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Username is required'
                    ]),
                    new Assert\Length([
                        'min' => 4,
                        'max' => 255,
                        'minMessage' => 'Username must be at least {{ limit }} characters long',
                        'maxMessage' => 'Username cannot be longer than {{ limit }} characters'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-z0-9_-]+$/',
                        'message' => 'Username can only contain lowercase letters, numbers, hyphens and underscores'
                    ]),
                    new UniqueUsername()
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Profile::class,
        ]);
    }
}
