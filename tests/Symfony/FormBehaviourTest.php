<?php
/**
 * Created by PhpStorm.
 * User: famoser
 * Date: 13/05/2017
 * Time: 14:25
 */

namespace AppBundle\Symfony;


use App\Entity\Member;
use App\Form\Member\MemberType;
use Symfony\Component\Form\Test\TypeTestCase;

class FormBehaviourTest extends TypeTestCase
{
    public function testObjectSet()
    {
        $form = $this->factory->create(MemberType::class);

        $object = new Member();
        $object->setName("custom name");
        $object->setEmail("org@email.ch");
        $form->setData($object);

        $form->submit(["new_member[thing][name]" => "my name", "email" => "me@mail.com"]);
        $this->assertTrue($form->getData() instanceof Member);
    }
}