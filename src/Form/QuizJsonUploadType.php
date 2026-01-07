<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class QuizJsonUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre du quiz',
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('description', TextType::class, [
                'required' => false,
                'label' => 'Description',
            ])
            ->add('jsonFile', FileType::class, [
                'label' => 'Fichier JSON',
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\File([
                        'mimeTypes' => ['application/json', 'text/json'],
                        'mimeTypesMessage' => 'Veuillez téléverser un fichier JSON valide.',
                    ]),
                ],
            ]);
    }
}
