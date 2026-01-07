<?php

namespace App\Form;

use App\Entity\User;
use App\Service\DomainChoiceProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class StudentRegistrationType extends AbstractType
{
    public function __construct(private readonly DomainChoiceProvider $choiceProvider)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'constraints' => [new NotBlank()],
                'label' => 'First name',
            ])
            ->add('lastName', TextType::class, [
                'constraints' => [new NotBlank()],
                'label' => 'Last name',
            ])
            ->add('email', EmailType::class, [
                'constraints' => [new NotBlank()],
                'label' => 'Email',
            ])
            ->add('password', PasswordType::class, [
                'mapped' => false,
                'constraints' => [new NotBlank()],
                'label' => 'Password',
            ])
            ->add('specialty', ChoiceType::class, [
                'choices' => $this->choiceProvider->getSpecialtyChoices(),
                'placeholder' => 'Sélectionnez votre spécialité',
                'constraints' => [new NotBlank()],
                'label' => 'Specialty',
                'required' => true,
            ])
            ->add('studyYear', ChoiceType::class, [
                'choices' => $this->choiceProvider->getStudyYearChoices(),
                'placeholder' => 'Sélectionnez votre année',
                'constraints' => [new NotBlank()],
                'label' => 'Study year',
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => ['Default', 'student'],
        ]);
    }
}
