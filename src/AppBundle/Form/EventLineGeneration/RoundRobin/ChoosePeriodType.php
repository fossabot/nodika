<?php
/**
 * Created by PhpStorm.
 * User: famoser
 * Date: 11/09/2017
 * Time: 14:30
 */

namespace AppBundle\Form\EventLineGeneration\RoundRobin;


use AppBundle\Form\EventLineGeneration\Base\BaseChoosePeriodType;
use AppBundle\Model\EventLineGeneration\RoundRobin\RoundRobinConfiguration;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChoosePeriodType extends BaseChoosePeriodType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->addFields($builder, ["translation_domain" => "round_robin"]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => RoundRobinConfiguration::class
        ));
    }
}