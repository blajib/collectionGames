<?php

namespace App\Controller;

use App\Entity\Game;
use App\Form\GameType;
use App\Others\Messages;
use App\Repository\GameRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route ("/game")
 */
class GameController extends AbstractController
{
    private function getSuccesMessage(Messages $messages){
        return $messages;
    }

    /**
     * @Route("/index", name="index_game")
     */
    public function index(GameRepository $gr, UserRepository $ur): Response
    {

        $gameList = $this->getUser()->getGames();
        //$gameList = $gr->findGamesUser($this->getUser());

        return $this->render('game/index.html.twig', [
            'gameList' => $gameList
        ]);
    }

    /**
     * @Route ("/add", name="add_game")
     */
    public function insertGame(Request $request, EntityManagerInterface $em)
    {
        $game = new Game();
        $form = $this->createForm(GameType::class, $game);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()){

            /*            try {
                            $user = $this->getUser();
                            $user->addGame($game);
                            $em->persist($user);
                            $em->flush();
                            $this->addFlash('success','Jeu ajouté');
                        }catch (\Exception $e){
                            $this->addFlash('error','problème lors de l\'enregistrement du jeux:   ');
                        }*/
            $game->setName(strtolower(str_replace(' ','¤',$game->getName())));
            $user = $this->getUser();
            $user->addGame($game);
            $em->persist($user);
            $em->persist($game);
            $em->flush();
            /** @var  $uploadFile */
            $uploadFile = $form['imageLink']->getData();
            if (!empty($uploadFile)) {

                $destination = $this->getParameter('kernel.project_dir') . '/public/uploads/gamesImages';
                $address = 'uploads/gamesImages/';
                $newFilename = "name-".$game->getName() ."-id-".$game->getId(). '-photoJeu.' . $uploadFile->guessExtension();
                $uploadFile->move(
                    $destination,
                    $newFilename
                );
                $game->setImageLink($address.$newFilename);
            }
            $em->persist($game);
            $em->flush();
            $this->addFlash('success','Jeu ajouté');


            return $this->redirectToRoute('add_game');
        }
        return $this->render('game/insert.html.twig',[
           "form" => $form->createView(),
        ]);
    }

    /**
     * @Route ("/remove/{idGame}", name="remove_game")
     */
    public function removeGame($idGame, GameRepository $gr,EntityManagerInterface $em)
    {
        try {
            /*$game = $gr->find($idGame);*/
            $user = $this->getUser();
            $game = $gr->find($idGame);
            //dd($game);
            // j'essaye d'enlever le fichier image avec le lien du jeux
            $imageLinkGame = $game->getImageLink();
            $user->removeGame($game);
            if($imageLinkGame != null){
                $file = new Filesystem();
                $link = str_replace("/alonealone", "", $imageLinkGame);
                //dd($imageLinkGame);
                $link = $this->getParameter('kernel.project_dir').$link;
                //dd($link);
                $file->remove($link);

            }
            $em->persist($user);
            $em->flush();
            /*$this->getUser()->removeGame($gr->find($idGame));
            */
            return $this->redirectToRoute("index_game");
        }catch (\Exception $e){
            dd("connard");
        }
    }

    /**
     * @Route ("/test_repo", name="game_test_repo")
     */
    public function testJoin(GameRepository $gr){

        //$csv = fopen($this->getParameter('kernel.project_dir').'/public/csv/video_games.csv','r');
        $reader = Reader::createFromPath($this->getParameter('kernel.project_dir').'\public\csv\video_games.csv','r');
        //dd($reader->fetchColumn("title"));
        //$results = $reader->addStreamFilter("title");
        dd($reader->output());
        //dd($csv["title"]);

        //$games = $gr->findJoin();

    }
    /**
     *
     */
    public function uploadCsvFile(Request $request, EntityManagerInterface $em){
        $csv = fopen($this->getParameter('kernel.project_dir').'\public\csv\video_games.csv');
        dd($csv);
    }
}
