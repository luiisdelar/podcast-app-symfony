<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Entity\Podcast;
use App\Form\PodcastType;
use App\Form\PodcastType2;
use Symfony\Component\HttpFoundation\Request;
use App\Form\UserType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\String\Slugger\SluggerInterface;


class AdminController extends AbstractController
{
    private $em; 
    
    public function __construct( EntityManagerInterface $em )
    {
        $this->em = $em;
    }

    #[Route('/admin', name: 'app_admin')]
    public function index(PaginatorInterface $paginator, Request $request): Response
    {

        $query = $this->em->getRepository( User::class )->findUsers();

        $pagination = $paginator->paginate(
            $query, 
            $request->query->getInt('page', 1), 
            10 
        );

        $user = new User();
        $form = $this->createForm( UserType::class, $user );

        return $this->render('admin/index.html.twig', [
            'users' => $pagination,
            'error' => ['state' => false],
            'success' => ['state' => false],
            'form' => $form->createView()
        ]);
    }

    #[Route('/admin/edit-user/{id}', name: 'edit_user_admin')]
    public function edit (User $user, Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $error = false;
        $success = false;
        $edit_form = $this->createForm( UserType::class, $user );
        $edit_form->handleRequest( $request );
        
        if ( $edit_form->isSubmitted() && $edit_form->isValid() ) {

            if ( $edit_form->get('password')->getData() != NULL ) {
                $plaintextPassword = $edit_form->get('password')->getData();
                $hashedPassword = $passwordHasher->hashPassword( $user, $plaintextPassword );
                $user->setPassword($hashedPassword);
            }

            $this->em->persist( $user );
            
            try {
                $this->em->flush();
                $success = true;
            } catch (\Throwable $th) {        
                $error = true;
            }           
        }

        return $this->render('admin/detail.html.twig', [
            'edit_form' => $edit_form->createView(),
            'error' => $error,
            'success' => $success
        ]);
    }

    #[Route('/admin/remove/user/{id}', name: 'remove_user_admin')]
    public function remove (User $user, PaginatorInterface $paginator, Request $request): Response
    {
        $user = $this->em->getRepository( User::class )->find($user->getId());
        $error = ['state' => false, 'message' => null];
        $success = ['state' => false, 'message' => null];

        try {
            $this->em->remove($user);
            $this->em->flush();
            $success['state'] = true;
            $success['message'] = 'User deleted';
        } catch (\Throwable $th) {
            $error['state'] = true;
            $error['message'] = 'Error trying delete user, try again.';
        }

    
        $user = new User();
        $form = $this->createForm( UserType::class, $user );
        
        $query = $this->em->getRepository( User::class )->findUsers();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10 
        );

        return $this->render('admin/index.html.twig', [
            'users' => $pagination,
            'error' => $error,
            'success' => $success,
            'form' => $form->createView()
        ]);
    }

    #[Route('/admin/podcasts/{id}', name: 'podcasts_admin')]
    public function podcasts (User $user): Response
    {
        $podcast = new Podcast();
        $form = $this->createForm( PodcastType::class, $podcast );
        $podcasts = $this->em->getRepository( Podcast::class )->findBy(['user' => $user->getId()]);

        return $this->render('admin/podcasts.html.twig', [
            'podcasts' => $podcasts,
            'form' => $form->createView(),
            'user' => $user,
            'error' => ['state' => false, 'message' => null],
            'success' => ['state' => false, 'message' => null]
        ]);


    }

    #[Route('/admin/podcast/edit/{id}', name: 'podcast_edit_admin')]
    public function editPodcast (Podcast $podcast, Request $request, SluggerInterface $slugger): Response
    {
        $error = false;
        $success = false;
        $edit_form = $this->createForm( PodcastType2::class, $podcast );
        $edit_form->handleRequest( $request );
       
        if ( $edit_form->isSubmitted() && $edit_form->isValid() ) {
           
            $image = $edit_form->get('image')->getData();
            if ( $image ) {
                $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$image->guessExtension();

                try {
                    $image->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $error = ['state' => true, 'message' => 'Error uploading image'];
                }
                $podcast->setImage($newFilename);
            }


            $this->em->persist( $podcast );
            
            try {
                $this->em->flush();
                $success = true;
            } catch (\Throwable $th) {        
                $error = true;
            }           
        } 

        return $this->render('admin/editpodcast.html.twig', [
            'edit_form' => $edit_form->createView(),
            'user' => $podcast->getUser(),
            'error' => $error,
            'success' => $success
        ]);
    }

    #[Route('/admin/podcast/remove/{id}', name: 'podcast_remove_admin')]
    public function removePodcast (Podcast $podcast): Response
    {
        $podcast = $this->em->getRepository( Podcast::class )->find($podcast->getId());
        $user = $podcast->getUser();
        $podcast_new = new Podcast();
        $form = $this->createForm( PodcastType::class, $podcast_new );
        $error = ['state' => false, 'message' => null];
        $success = ['state' => false, 'message' => null];
        
        
        try {
            $this->em->remove($podcast);    
            $this->em->flush();
            $success = ['state' => true, 'message' => 'Podcast Deleted!'];
            
        } catch (\Throwable $th) {            
            $error = ['state' => true, 'message' => 'Error deleting podcast'];
        }    
        
        $podcasts = $this->em->getRepository( Podcast::class )->findBy(['user' => $user->getId()]);
        
        
        return $this->render('admin/podcasts.html.twig', [
            'podcasts' => $podcasts,
            'form' => $form->createView(),
            'user' => $user,
            'error' => $error,
            'success' => $success
        ]);
    }

}
