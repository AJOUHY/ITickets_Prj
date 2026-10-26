<?php

namespace App\Form;

use App\Entity\Categorie;
use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\TicketLieu;
use App\Enum\TicketStatus;
use App\Enum\TicketType;
use App\Enum\TicketUrgence;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'demande' => TicketType::DEMANDE,
                    'incident' => TicketType::INCIDENT,
                ],
                'choice_label' => fn ($choice) => match ($choice) {
                    TicketType::DEMANDE => 'demande',
                    TicketType::INCIDENT => 'incident',
                    default => (string) $choice,
                },
                'choice_value' => static fn (?TicketType $choice) => $choice?->value,
            ])
            ->add('categorie', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'titre',
                'label' => 'Catégorie',
                'placeholder' => 'Choisir une catégorie',
            ])
            ->add('urgence', ChoiceType::class, [
                'label' => 'Urgence',
                'choices' => [
                    'basse' => TicketUrgence::BASSE,
                    'moyenne' => TicketUrgence::MOYENNE,
                    'haute' => TicketUrgence::HAUTE,
                    'très haute' => TicketUrgence::TRES_HAUTE,
                ],
                'choice_label' => fn ($choice) => match ($choice) {
                    TicketUrgence::BASSE => 'basse',
                    TicketUrgence::MOYENNE => 'moyenne',
                    TicketUrgence::HAUTE => 'haute',
                    TicketUrgence::TRES_HAUTE => 'très haute',
                    default => (string) $choice,
                },
                'choice_value' => static fn (?TicketUrgence $choice) => $choice?->value,
            ])
            ->add('lieu', ChoiceType::class, [
                'label' => 'Lieu',
                'choices' => [
                    'rabat' => TicketLieu::RABAT,
                    'casablanca' => TicketLieu::CASABLANCA,
                ],
                'choice_label' => fn ($choice) => match ($choice) {
                    TicketLieu::RABAT => 'rabat',
                    TicketLieu::CASABLANCA => 'casablanca',
                    default => (string) $choice,
                },
                'choice_value' => static fn (?TicketLieu $choice) => $choice?->value,
            ])
            ->add('titre', TextType::class, [
                'label' => 'Titre',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['rows' => 5],
            ]);

        if ($options['include_status']) {
            $builder->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Ouvert' => TicketStatus::OPENED,
                    'En cours' => TicketStatus::IN_PROGRESS,
                    'En attente' => TicketStatus::PENDING,
                    'Résolu' => TicketStatus::RESOLVED,
                    'Fermé' => TicketStatus::CLOSED,
                ],
                'choice_label' => fn ($choice) => match ($choice) {
                    TicketStatus::OPENED => 'Ouvert',
                    TicketStatus::IN_PROGRESS => 'En cours',
                    TicketStatus::PENDING => 'En attente',
                    TicketStatus::RESOLVED => 'Résolu',
                    TicketStatus::CLOSED => 'Fermé',
                    default => (string) $choice,
                },
                'choice_value' => static fn (?TicketStatus $choice) => $choice?->value,
            ]);
        }

        if ($options['include_affecte_a']) {
            $builder->add('affecteA', EntityType::class, [
                'class' => User::class,
                'label' => 'Technicien affecté',
                'required' => false,
                'placeholder' => 'Non affecté',
                'query_builder' => static function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where("u.roles LIKE :role")
                        ->setParameter('role', '%ROLE_TECHNICIEN%')
                        ->orderBy('u.lastName', 'ASC');
                },
                'choice_label' => static fn (User $user) => $user->getFirstName() . ' ' . $user->getLastName(),
                'attr' => [
                    'class' => 'form-select',
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
            'include_status' => false,
            'include_affecte_a' => false,
        ]);
    }
}
