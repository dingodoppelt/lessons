<!doctype html>
<html lang="de">
<head>
    <link rel="stylesheet" type="text/css" href="hell.css">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schüler-DB</title>
</head>
<?php
$db = new mysqli('localhost', 'client', 'lighttpd_client', 'schueler');
$schueler = $db->query("SELECT ID, vorname, nachname, vertraege.anfang, vertraege.ende, vertraege.gruppe FROM schueler JOIN vertraege ON ID = schuelerID WHERE vertraege.ende > NOW() || vertraege.ende IS NULL && vertraege.gruppe IS NULL;") or die($db->error);
if (isset($_POST['erledigt'])) {
    foreach ($_POST['erledigt'] as $ID) {
        $db->query("UPDATE aufgaben SET erledigt = NOW() WHERE aufgID = " . $ID . ";");
    }
}
if ($_POST['hausaufgaben'] != '') {
    $hausaufgabe = explode(';', $_POST['hausaufgaben']);
    foreach ($hausaufgabe as $HA) {
        $db->query("INSERT INTO aufgaben (schuelerID, datum, aufgabe) VALUES ('" . $_GET['schuelerWahl'] . "', NOW(), '" . $HA . "');") or die($db->error);
    }
}
if ($_POST['inhalte'] != '') {
    $db->query("INSERT INTO unterricht (schuelerID, datum, inhalte, online) VALUES ('" . $_GET['schuelerWahl'] . "', NOW(), '" . $_POST['inhalte'] . "', '" . intval(isset($_POST['online'])) . "');") or die($db->error);
    $untID = $db->insert_id;
    foreach ($_POST['abwesend'] as $abwesender) {
        $db->query("INSERT INTO abwesenheit (schuelerID, untID) VALUES (" . $abwesender . ", " . $untID . ");") or die($db->error);
    }
}
if (isset($_GET['schuelerWahl'])) {
    $unterricht = $db->query("SELECT inhalte, schuelerID, datum FROM unterricht WHERE schuelerID = " . $_GET['schuelerWahl'] . " ORDER BY datum DESC;") or die($db->error);
    $aufgaben = $db->query("SELECT * FROM aufgaben WHERE schuelerID = " . $_GET['schuelerWahl'] . " && erledigt IS NULL ORDER BY datum DESC;") or die($db->error);
    $teilnehmerX = $db->query("SELECT ID, vorname, nachname, vertraege.anfang, vertraege.ende, vertraege.gruppe FROM schueler JOIN vertraege ON ID = schuelerID WHERE vertraege.gruppe = " .$_GET['schuelerWahl'] . ";") or die($db->error);
}
?>
<body>
    <form action="" method="GET" id="waehleSchueler">
        <select name="schuelerWahl" form="waehleSchueler" onchange="this.form.submit()">
            <option value="0">Schüler wählen</option>
            <?php
                while ($name = $schueler->fetch_object()) {
                    if (isset($_GET['schuelerWahl']) && $_GET['schuelerWahl'] == $name->ID) {
                        echo '<option value="' . $name->ID . '" selected>' . $name->vorname . ' ' . $name->nachname . '</option>' . PHP_EOL;
                    } else {
                        echo '<option value="' . $name->ID . '">' . $name->vorname . ' ' . $name->nachname . '</option>' . PHP_EOL;
                    }
                }
            ?>
        </select>
    </form>
    <fieldset>
        <legend>heute:</legend>
        <form action="" method="POST">
        <?php
            while ($aufgabe = $aufgaben->fetch_object()) {
                $tage = round((time() - strtotime($aufgabe->datum)) / 86400);
                echo '<input class="schalter" type="checkbox" name="erledigt[]" value="' . $aufgabe->aufgID . '" id="' . $aufgabe->aufgabe . '">';
                echo '<label for="' . $aufgabe->aufgabe . '" class="schalter">' . $aufgabe->aufgabe . ' (' . $tage . ')</label>' . PHP_EOL;
            }
            $db->close();
        ?>
            <input type="text" name="inhalte" placeholder="Unterrichtsthemen" autocomplete="off">
            <input type="text" name="hausaufgaben" placeholder="Aufg1;Aufg2;..." autocomplete="off">
            <input type="submit" value="Stunde speichern..." onclick="return confirm('Themen und Hausaufgaben wirklich speichern?');">
        </fieldset>
        <?php
            echo '<input type="checkbox" name="online">onlinestunde</input></br>';
            while ($teilnehmer = $teilnehmerX->fetch_object()) {
                echo '<input type="checkbox" name="abwesend[]" value="' . $teilnehmer->ID . '" id="' . $teilnehmer->ID . '">' . $teilnehmer->vorname . '</input>';
            }
        ?>
    </form>
    <fieldset>
        <legend><button class="button1" onclick="stundeZurueck()"><</button><p id='vorigeStd'></p><button class="button2" onclick="stundeVor()">></button></legend>
        <p id='historie'></p>
        <script type="text/javascript">
            var jArray = <?php echo json_encode($unterricht->fetch_all()); ?>;
            var index = 0;
            setzeDatum();
            function setzeDatum() {
                var _datum = new Date(jArray[index][2]);
                document.getElementById('historie').innerHTML = jArray[index][0];
                document.getElementById('vorigeStd').innerHTML = _datum.toLocaleDateString('de-DE');
            }
            function stundeZurueck() {
                index++;
                if (index >= jArray.length) { index=0; }
                setzeDatum();
            }
            function stundeVor() {
                index--;
                if (index < 0) { index = jArray.length-1; }
                setzeDatum();
            }
        </script>
    </fieldset>
    <p id='platzhalter'>___</p>
</body>
</html>
