<?php

namespace App\Controller;

use App\Form\Model\ExportModel;
use App\Form\Type\ExportType;
use App\Service\ExportService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ExportController extends AbstractController
{
    /**
     * @Route("/admin/exports", name="exports")
     *
     * @IsGranted("ROLE_ADMIN")
     */
    public function export(Request $request, ExportService $service): Response
    {
        $exportModel = new ExportModel();
        $form = $this->createForm(ExportType::class, $exportModel)->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            return $service->saveExport($exportModel);
        }

        return $this->render('admin/export/index.html.twig', [
            'form' => $form,
        ]);
    }
}
