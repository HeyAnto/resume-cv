<?php

namespace App\Form;

use App\Entity\Certification;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CertificationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Certification Title',
                'attr' => [
                    'placeholder' => 'AWS Certified Solutions Architect',
                    'class' => 'form-control',
                    'data-counter' => 'title-counter',
                    'data-max' => '98',
                    'autocomplete' => 'off'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Certification title is required']),
                    new Assert\Length([
                        'max' => 98,
                        'maxMessage' => 'Title cannot be longer than {{ limit }} characters'
                    ])
                ]
            ])
            ->add('authority', TextType::class, [
                'label' => 'Issuing Authority',
                'attr' => [
                    'placeholder' => 'Amazon Web Services (AWS)',
                    'class' => 'form-control',
                    'data-counter' => 'authority-counter',
                    'data-max' => '98',
                    'autocomplete' => 'off'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Issuing authority is required']),
                    new Assert\Length([
                        'max' => 98,
                        'maxMessage' => 'Authority cannot be longer than {{ limit }} characters'
                    ])
                ]
            ])
            ->add('authorityLink', UrlType::class, [
                'label' => 'Authority Website',
                'required' => false,
                'attr' => [
                    'placeholder' => 'https://aws.amazon.com/certification/',
                    'class' => 'form-control',
                    'data-counter' => 'authorityLink-counter',
                    'data-max' => '98',
                    'autocomplete' => 'off'
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 98,
                        'maxMessage' => 'Authority website cannot be longer than {{ limit }} characters'
                    ])
                ]
            ])
            ->add('issuedDate', DateType::class, [
                'label' => 'Issue Date',
                'widget' => 'single_text',
                'html5' => true,
                'input' => 'datetime_immutable',
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Issue date is required']),
                    new Assert\LessThanOrEqual([
                        'value' => new \DateTime(),
                        'message' => 'Issue date cannot be in the future'
                    ])
                ]
            ])
            ->add('expirationDate', DateType::class, [
                'label' => 'Expiration Date (Optional)',
                'widget' => 'single_text',
                'html5' => true,
                'input' => 'datetime_immutable',
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new Assert\GreaterThan([
                        'propertyPath' => 'parent.all[issuedDate].data',
                        'message' => 'Expiration date must be after issue date'
                    ])
                ]
            ])
            ->add('credentialId', TextType::class, [
                'label' => 'Credential ID',
                'attr' => [
                    'placeholder' => 'ABC123456789',
                    'class' => 'form-control',
                    'data-counter' => 'credentialId-counter',
                    'data-max' => '255',
                    'autocomplete' => 'off'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Credential ID is required']),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'Credential ID cannot be longer than {{ limit }} characters'
                    ])
                ]
            ])
            ->add('credentialUrl', UrlType::class, [
                'label' => 'Credential URL',
                'attr' => [
                    'placeholder' => 'https://www.credly.com/badges/...',
                    'class' => 'form-control',
                    'data-counter' => 'credentialUrl-counter',
                    'data-max' => '255',
                    'autocomplete' => 'off'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Credential URL is required']),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'Credential URL cannot be longer than {{ limit }} characters'
                    ])
                ]
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Save Certification',
                'attr' => [
                    'class' => 'btn btn-primary'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Certification::class,
        ]);
    }
}