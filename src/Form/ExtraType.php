<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ExtraType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('elements', CollectionType::class, ["entry_type" => ElementType::class, 'allow_add' => true])
            ->add('validElements', CollectionType::class, ["entry_type" => TextType::class, 'allow_add' => true])
            ->add('byScore', CollectionType::class, ["entry_type" => ByScoreType::class, 'allow_add' => true]);
    }
}
