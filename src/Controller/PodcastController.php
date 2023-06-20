<?php

namespace App\Controller;
use App\Entity\Podcast;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\PodcastType;
use App\Form\PodcastType2;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class PodcastController extends AbstractController
{
    private $em; 
    
    public function __construct( EntityManagerInterface $em )
    {
        $this->em = $em;
    }

    #[Route('/podcast', name: 'index_podcast')]
    public function index (): Response
    {   

        $podcast = new Podcast();
        $form = $this->createForm( PodcastType::class, $podcast );
        $user = $this->getUser();
        $podcasts = $this->em->getRepository( Podcast::class )->findBy(['user' => $user->getId()]);
       
        return $this->render('podcast/index.html.twig', [
            'podcasts' => $podcasts,
            'form' => $form->createView(),
            'user' => $user,
            'error' => ['state' => false, 'message' => null],
            'success' => ['state' => false, 'message' => null]
        ]);
    }


    #[Route('/new/podcast', name: 'new_podcast')]
    public function new (Request $request, SluggerInterface $slugger): Response
    {
        $podcast = new Podcast();
        $form = $this->createForm( PodcastType::class, $podcast );
        $form->handleRequest( $request );
        $error = ['state' => false, 'message' => null];
        $success = ['state' => false, 'message' => null];

        if ( $form->isSubmitted() && $form->isValid() ) {
            
            $image = $form->get('image')->getData();
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
            } else {
                $podcast->setImage('audio_icon.jpg');
            }

            $audio = $form->get('audio')->getData();
            if ( $audio ) {
                $originalFilename = pathinfo($audio->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$audio->guessExtension();

                try {
                    $audio->move(
                        $this->getParameter('audios_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $error = ['state' => true, 'message' => 'Error uploading podcast, try again'];
                }
                $podcast->setAudio($newFilename);
            }

            $user = $this->getUser();
            $podcast->setUser( $user );
            
            try {
                $this->em->persist( $podcast );
                $this->em->flush();
                $success = ['state' => true, 'message' => 'Podcast uploaded!'];
            } catch (\Throwable $th) {
                $error = ['state' => true, 'message' => 'Error uploading podcast, try again'];
            }

        }
        
        $podcasts = $this->em->getRepository( Podcast::class )->findBy(['user' => $user->getId()]);

        return $this->render('podcast/index.html.twig', [
            'podcasts' => $podcasts,
            'form' => $form->createView(),
            'user' => $user,
            'error' => $error,
            'success' => $success
        ]);
       
    }
    
    #[Route('/podcast/edit/{id}', name: 'edit_podcast')]
    public function edit (Podcast $podcast, Request $request, SluggerInterface $slugger): Response
    {
        $error = false;
        $success = false;
        $edit_form = $this->createForm( PodcastType2::class, $podcast );
        $edit_form->handleRequest( $request );
        $user = $this->getUser();

        if ( $podcast->getUser()->getId() != $user->getId() ) {
            return $this->redirectToRoute('app_login');
        }

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

        return $this->render('podcast/detail.html.twig', [
            'edit_form' => $edit_form->createView(),
            'user' => $user,
            'error' => $error,
            'success' => $success
        ]);
    }
    

    #[Route('/update/podcast', name: 'update_podcast')]
    public function update (): Response
    {
        $podcast = $this->em->getRepository( Podcast::class )->find(3);
        $podcast->setTitle('Nuevppppp');
        $this->em->flush();

        return $this->json(['success'=>'true'],200);
    }
    
    #[Route('/remove/podcast/{id}', name: 'remove_podcast')]
    public function remove (Podcast $podcast): Response
    {
        $user = $this->getUser();
        $podcast = $this->em->getRepository( Podcast::class )->find($podcast->getId());
        $podcast_new = new Podcast();
        $form = $this->createForm( PodcastType::class, $podcast_new );
        $error = ['state' => false, 'message' => null];
        $success = ['state' => false, 'message' => null];
        
        if ($podcast->getUser()->getId() == $user->getId()) {
            $this->em->remove($podcast);    
            $this->em->flush();
            $success = ['state' => true, 'message' => 'Podcast Deleted!'];
        } else {
            $error = ['state' => true, 'message' => 'Error deleting podcast'];
        }
        
        $podcasts = $this->em->getRepository( Podcast::class )->findBy(['user' => $user->getId()]);
        
        return $this->render('podcast/index.html.twig', [
            'podcasts' => $podcasts,
            'form' => $form->createView(),
            'user' => $user,
            'error' => $error,
            'success' => $success
        ]);
    }
}
