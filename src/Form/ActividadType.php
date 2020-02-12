<?php
namespace App\Form;

use App\Entity\Actividad;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActividadType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('nombre')
      ->add('objetivo')
      ->add('dominio')
      ->add('idioma')
      ->add('tipoPlanificacion')
      ->add('estado')
      ->add('save', SubmitType::class)
    ;
  }
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => Actividad::class,
      'csrf_protection' => false
    ));
  }
}