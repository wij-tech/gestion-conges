<?php

function compter_employes_en_conge(PDO $pdo, ?int $divisionId = null): array
{
    $annee = (int) date('Y');
    $debutAnnee = new DateTimeImmutable($annee . '-01-01');
    $finAnnee = new DateTimeImmutable($annee . '-12-31');
    $debutSemaine = new DateTimeImmutable('monday this week');
    $finSemaine = $debutSemaine->modify('+6 days');
    $debutMois = new DateTimeImmutable('first day of this month');
    $finMois = new DateTimeImmutable('last day of this month');

    $intervalles = [
        'semaine' => [],
        'mois' => [],
        'annee' => []
    ];

    for ($jour = $debutSemaine; $jour <= $finSemaine; $jour = $jour->modify('+1 day')) {
        $intervalles['semaine'][] = [$jour, $jour, $jour->format('D d/m')];
    }

    $premierJour = (int) $debutMois->format('N');
    $nombreJours = (int) $finMois->format('j');
    $nombreSemaines = (int) ceil($nombreJours / 7);
    for ($semaine = 0; $semaine < $nombreSemaines; $semaine++) {
        $debut = $debutMois->modify('+' . ($semaine * 7) . ' days');
        $fin = $debut->modify('+6 days');
        if ($fin > $finMois) {
            $fin = $finMois;
        }
        $intervalles['mois'][] = [$debut, $fin, 'S' . ($semaine + 1)];
    }

    for ($mois = 1; $mois <= 12; $mois++) {
        $debut = $debutAnnee->setDate($annee, $mois, 1);
        $fin = $debut->modify('last day of this month');
        $intervalles['annee'][] = [$debut, $fin, $debut->format('M')];
    }

    $resultats = ['semaine' => [], 'mois' => [], 'annee' => []];
    foreach ($intervalles as $mode => $liste) {
        foreach ($liste as [$debut, $fin, $label]) {
            $join = '';
            $divisionCondition = '';
            $params = [$fin->format('Y-m-d'), $debut->format('Y-m-d')];
            if ($divisionId !== null) {
                $join = ' INNER JOIN employes ON employes.id = conges.employe_id';
                $divisionCondition = ' AND employes.division_id = ?';
                $params[] = $divisionId;
            }

            $stmt = $pdo->prepare(
                "SELECT COUNT(DISTINCT conges.employe_id)
                 FROM conges" . $join . "
                 WHERE conges.statut = 'validee'
                   AND conges.date_debut <= ?
                   AND conges.date_fin >= ?" . $divisionCondition
            );
            $stmt->execute($params);
            $resultats[$mode]['labels'][] = $label;
            $resultats[$mode]['values'][] = (int) $stmt->fetchColumn();
        }
    }

    return $resultats;
}
