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
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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
            ->add('displayName', TextType::class, [
                'label' => 'Display Name',
                'attr' => [
                    'placeholder' => 'John Doe',
                    'required' => true,
                    'data-counter' => 'displayName-counter',
                    'data-max' => '48',
                    'autocomplete' => 'off'
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
                    'data-max' => '12',
                    'autocomplete' => 'off'
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
                    'data-max' => '32',
                    'autocomplete' => 'off'
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
                    'data-max' => '32',
                    'autocomplete' => 'off'
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
                    'data-max' => '160',
                    'autocomplete' => 'off'
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
                    'data-max' => '96',
                    'autocomplete' => 'off'
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
                    'data-max' => '96',
                    'autocomplete' => 'off'
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 96,
                        'maxMessage' => 'Website link cannot be longer than {{ limit }} characters'
                    ]),
                    new Assert\Url([
                        'message' => 'Please enter a valid URL (e.g., https://example.com)'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^https?:\/\//',
                        'message' => 'Website URL must start with http:// or https://'
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Profile::class,
            'constraints' => [
                new Assert\Callback([$this, 'validateWebsiteFields'])
            ]
        ]);
    }

    public function validateWebsiteFields($profile, ExecutionContextInterface $context): void
    {
        $websiteName = $profile->getWebsiteName();
        $websiteLink = $profile->getWebsiteLink();

        if ((!empty($websiteName) && empty($websiteLink)) || (empty($websiteName) && !empty($websiteLink))) {
            if (!empty($websiteName) && empty($websiteLink)) {
                $context->buildViolation('Website link is required when website name is provided')
                    ->atPath('websiteLink')
                    ->addViolation();
            }

            if (empty($websiteName) && !empty($websiteLink)) {
                $context->buildViolation('Website name is required when website link is provided')
                    ->atPath('websiteName')
                    ->addViolation();
            }
        }
    }
}
