<?php

function statut_conge_label(string $statut): string
{
    return [
        'en_attente_chef' => 'En attente du chef',
        'en_attente_admin' => "En attente de l'administrateur",
        'validee' => 'Validée',
        'refusee' => 'Refusée',
        'Annule' => 'Annulée',
        'En attente' => 'En attente',
        'Valide' => 'Validée',
        'Refuse' => 'Refusée'
    ][$statut] ?? 'Statut inconnu';
}

function statut_conge_badge(string $statut): string
{
    $styles = [
        'en_attente_chef' => ['badge-attente-chef', 'fa-user-clock'],
        'en_attente_admin' => ['badge-attente-admin', 'fa-user-shield'],
        'validee' => ['badge-validee', 'fa-circle-check'],
        'refusee' => ['badge-refusee', 'fa-circle-xmark'],
        'Annule' => ['badge-annulee', 'fa-ban'],
        'En attente' => ['badge-attente-chef', 'fa-clock'],
        'Valide' => ['badge-validee', 'fa-circle-check'],
        'Refuse' => ['badge-refusee', 'fa-circle-xmark']
    ];
    [$classe, $icone] = $styles[$statut] ?? ['badge-attente-admin', 'fa-circle-question'];
    return '<span class="badge ' . $classe . '"><i class="fa-solid ' . $icone . '"></i>'
        . e(statut_conge_label($statut)) . '</span>';
}
