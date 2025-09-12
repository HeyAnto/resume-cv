<?php

namespace App\Form;

use App\Entity\Profile;
use App\Validator\UniqueUsername;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('profilePicture', FileType::class, [
                'label' => 'Profile Picture',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'accept' => 'image/*'
                ],
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp'
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file (JPEG, PNG, WebP)',
                    ])
                ]
            ])
            ->add('username', TextType::class, [
                'label' => 'Username',
                'attr' => [
                    'placeholder' => 'johndoe',
                    'required' => true,
                    'data-max' => '255'
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
            ])
            ->add('displayName', TextType::class, [
                'label' => 'Display Name',
                'attr' => [
                    'placeholder' => 'John Doe',
                    'required' => true,
                    'data-counter' => 'displayName-counter',
                    'data-max' => '48'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Display name is required'
                    ]),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 48,
                        'minMessage' => 'Display name must be at least {{ limit }} characters long',
                        'maxMessage' => 'Display name cannot be longer than {{ limit }} characters'
                    ])
                ]
            ])
            ->add('pronouns', TextType::class, [
                'label' => 'Pronouns',
                'required' => false,
                'attr' => [
                    'placeholder' => 'They/them',
                    'data-counter' => 'pronouns-counter',
                    'data-max' => '12'
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 12,
                        'maxMessage' => 'Pronouns cannot be longer than {{ limit }} characters'
                    ])
                ]
            ])
            ->add('job', TextType::class, [
                'label' => 'Job Title',
                'attr' => [
                    'placeholder' => 'Architect, painter, etc',
                    'required' => true,
                    'data-counter' => 'job-counter',
                    'data-max' => '32'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Job title is required'
                    ]),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 32,
                        'minMessage' => 'Job title must be at least {{ limit }} characters long',
                        'maxMessage' => 'Job title cannot be longer than {{ limit }} characters'
                    ])
                ]
            ])
            ->add('location', TextType::class, [
                'label' => 'Location',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Where you\'re based',
                    'data-counter' => 'location-counter',
                    'data-max' => '32'
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 32,
                        'maxMessage' => 'Location cannot be longer than {{ limit }} characters'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Bio',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Tell us a little more about yourself',
                    'data-counter' => 'description-counter',
                    'data-max' => '160'
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 160,
                        'maxMessage' => 'Bio cannot be longer than {{ limit }} characters'
                    ])
                ]
            ])
            ->add('websiteName', TextType::class, [
                'label' => 'Display Website Name',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Resume.cv',
                    'data-counter' => 'websiteName-counter',
                    'data-max' => '96'
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 96,
                        'maxMessage' => 'Website name cannot be longer than {{ limit }} characters'
                    ])
                ]
            ])
            ->add('websiteLink', TextType::class, [
                'label' => 'Website',
                'required' => false,
                'attr' => [
                    'placeholder' => 'https://www.resume.cv',
                    'data-counter' => 'websiteLink-counter',
                    'data-max' => '96'
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 96,
                        'maxMessage' => 'Website link cannot be longer than {{ limit }} characters'
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Profile::class,
        ]);
    }
}
