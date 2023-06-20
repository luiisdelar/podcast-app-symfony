<?php

namespace App\Controller;
use App\Entity\User; 
use App\Form\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    private $em; 
    
    public function __construct( EntityManagerInterface $em )
    {
        $this->em = $em;
    }

    #[Route('/register', name: 'register_user')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $register_form = $this->createForm( UserType::class, $user );
        $register_form->handleRequest( $request );

        if ( $register_form->isSubmitted() && $register_form->isValid() ) {

            $plaintextPassword = $register_form->get('password')->getData();
            $hashedPassword = $passwordHasher->hashPassword( $user, $plaintextPassword );
            $user->setPassword($hashedPassword);

            $user->setRoles(['ROLE_USER']);
            $this->em->persist( $user );
            $this->em->flush();

            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('app_admin', ['message' => 'User created successfully']);
            }

            return $this->redirectToRoute('app_login', ['message' => 'Registered user successfully']);
        }
        return $this->render('user/index.html.twig', [
            'register_form' => $register_form->createView(),
        ]);
    }
}
