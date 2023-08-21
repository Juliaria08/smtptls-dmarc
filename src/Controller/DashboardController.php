<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Domains;
use App\Entity\Reports;
use App\Entity\Seen;
use App\Entity\Logs;

class DashboardController extends AbstractController
{
    private $em;
    private $router;
    private $translator;

    public function __construct(EntityManagerInterface $em, UrlGeneratorInterface $router, TranslatorInterface $translator)
    {
        $this->em = $em;
        $this->router = $router;
        $this->translator = $translator;
    }

    #[Route('/', name: 'app_dashboard')]
    public function index(): Response
    {
        if (!file_exists(dirname(__FILE__).'/../../.env.local')) {
            return $this->redirectToRoute('app_setup');
        }

        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $repository = $this->em->getRepository(Domains::class);
        $domains = $repository->findBy(array('id' => $this->getUser()->getRoles()));

        $repository = $this->em->getRepository(Reports::class);
        if(in_array("ROLE_ADMIN", $this->getUser()->getRoles())) {
            $reports = $repository->findBy(array(),array('id' => 'DESC'),10,0);
        } else {
            $reports = $repository->findBy(array('domain' => $domains),array('id' => 'DESC'),10,0);
        }
        $totalreports = $repository->getTotalRows($domains);   


        $repository = $this->em->getRepository(Seen::class);
        $reportsseen = $repository->getSeen($reports, $this->getUser()->getId());

        $repository = $this->em->getRepository(Logs::class);
        $logs = $repository->findBy(array(),array('id' => 'DESC'),10, 0);

        return $this->render('dashboard/index.html.twig', [
            'menuactive' => 'dashboard',
            'breadcrumbs' => array('0' => array('name' => $this->translator->trans("Dashboard"), 'url' => $this->router->generate('app_dashboard'))),
            'reports' => $reports,
            'reportsseen' => $reportsseen,
            'logs' => $logs,
        ]);
    }
}