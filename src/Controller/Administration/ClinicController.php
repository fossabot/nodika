<?php

/*
 * This file is part of the nodika project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Administration;

use App\Controller\Base\BaseFormController;
use App\Entity\Clinic;
use App\Form\Clinic\RemoveType;
use App\Model\Breadcrumb;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/clinics")
 * @Security("has_role('ROLE_USER')")
 */
class ClinicController extends BaseFormController
{
    /**
     * @Route("/new", name="administration_clinic_new")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function newAction(Request $request)
    {
        $myForm = $this->handleCreateForm($request, new Clinic());

        if ($myForm instanceof Response) {
            return $myForm;
        }

        $arr['form'] = $myForm->createView();

        return $this->render('administration/clinic/new.html.twig', $arr);
    }

    /**
     * @Route("/{clinic}/edit", name="administration_clinic_edit")
     *
     * @param Request $request
     * @param Clinic $clinic
     *
     * @return Response
     */
    public function editAction(Request $request, Clinic $clinic)
    {
        $myForm = $this->handleUpdateForm($request, $clinic);

        if ($myForm instanceof Response) {
            return $myForm;
        }

        $arr['form'] = $myForm->createView();

        return $this->render('administration/clinic/edit.html.twig', $arr);
    }

    /**
     * @Route("/{clinic}/remove", name="administration_clinic_remove")
     *
     * @param Request $request
     * @param Clinic $clinic
     *
     * @return Response
     */
    public function removeAction(Request $request, Clinic $clinic)
    {
        $canDelete = $clinic->getEvents()->count() == 0;
        $myForm = $this->handleForm(
            $this->createForm(RemoveType::class, $clinic),
            $request,
            function () use ($clinic, $canDelete) {
                $clinic->delete();
                if ($canDelete) {
                    $this->fastRemove($clinic);
                } else {
                    $clinic->delete();
                    $this->fastSave($clinic);
                }
            }
        );

        if ($myForm instanceof Response) {
            return $myForm;
        }

        $arr["can_delete"] = $canDelete;
        $arr['form'] = $myForm->createView();

        return $this->render('administration/clinic/remove.html.twig', $arr);
    }

    /**
     * get the breadcrumbs leading to this controller
     *
     * @return Breadcrumb[]
     */
    protected function getIndexBreadcrumbs()
    {
        return [
            new Breadcrumb(
                $this->generateUrl("administration_index"),
                $this->getTranslator()->trans("index.title", [], "administration")
            ),
            new Breadcrumb(
                $this->generateUrl("administration_clinics"),
                $this->getTranslator()->trans("clinics.title", [], "administration")
            )
        ];
    }
}