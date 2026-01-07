<?php

namespace App\Controller;

use App\Form\StudentRegistrationType;
use App\Service\RegistrationService;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/register', name: 'app_register')]
    public function register(
        Request $request,
        RegistrationService $registrationService,
        UserRepository $userRepository
    ): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('student_dashboard');
        }

        $form = $this->createForm(StudentRegistrationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            if ($userRepository->findOneBy(['email' => $data->getEmail()])) {
                $this->addFlash('error', 'Un compte existe déjà avec cet email.');
            } else {
                $registrationService->registerStudent(
                    $data->getEmail(),
                    (string) $form->get('password')->getData(),
                    $data->getFirstName(),
                    $data->getLastName(),
                    $data->getSpecialty(),
                    $data->getStudyYear()
                );

                $this->addFlash('success', 'Registration submitted. An admin will activate your account.');

                return new RedirectResponse($this->generateUrl('app_login'));
            }
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
