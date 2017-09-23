<?php
/**
 * Created by PhpStorm.
 * User: famoser
 * Date: 30/04/2017
 * Time: 21:26
 */

namespace AppBundle\Form\Newsletter;


use AppBundle\Entity\Newsletter;
use AppBundle\Entity\Traits\CommunicationTrait;
use AppBundle\Entity\Traits\PersonTrait;
use AppBundle\Enum\SubmitButtonType;
use AppBundle\Form\BaseAbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegisterForPreviewType extends BaseAbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder = Newsletter::getBuilderStatic($builder);

        $this->addTrait($builder, PersonTrait::class);
        $this->addTrait($builder, CommunicationTrait::class);


        $this->addSubmit($builder, SubmitButtonType::SEND);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Newsletter::class,
        ));
    }
}