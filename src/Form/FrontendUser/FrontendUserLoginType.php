<?php

/*
 * This file is part of the nodika project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\FrontendUser;

use App\Entity\FrontendUser;
use App\Form\Generic\LoginType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FrontendUserLoginType extends LoginType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => FrontendUser::class,
        ]);
    }
}
