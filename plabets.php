<?php

if(!isset($argv[1])) die("Race Date Not Entered!!\n");

if(isset($argv[2])) $stage = trim($argv[2]);
else $stage = 1;

$step = "plabets$stage";
$raceDate = trim($argv[1]);
$currentDir = __DIR__ . DIRECTORY_SEPARATOR . $raceDate;

$allRacesRunners = include($currentDir . DIRECTORY_SEPARATOR . "1.php");
$allRacesOdds = include($currentDir . DIRECTORY_SEPARATOR . "odds.php");
$history = include(__DIR__ . DIRECTORY_SEPARATOR . "triohistory$stage.php");
$outFile = $currentDir . DIRECTORY_SEPARATOR . "$step.php";

if(file_exists($outFile)){
    $oldData = include($outFile);
}

$totalRaces = count($allRacesRunners);

$outtext = "<?php\n\n";
$outtext .= "return [\n";

for ($raceNumber = 1; $raceNumber <= $totalRaces; $raceNumber++) {
    if(!isset($allRacesRunners[$raceNumber])) continue;
    if(isset($oldData)){
        if(isset($oldData[$raceNumber]['favorites'])) $oldFavorites = explode(", ", $oldData[$raceNumber]['favorites']);
    }
    if(isset($oldFavorites)) $favorites = $oldFavorites;
    else $favorites = [];
    
    $runners = explode(", ", $allRacesRunners[$raceNumber]['Runners']);
    $favorite = $runners[0];
    if(!in_array($favorite, $favorites)) $favorites[] = $favorite;
    $raceData1 = $history[$raceNumber][$favorite];
    $racetext = "";
   
    $racetext .= "\t'$raceNumber' => [\n";
    $racetext .= "\t\t/**\n";
    $racetext .= "\t\tRace $raceNumber\n";
    $racetext .= "\t\t*/\n";
    $racetext .= "\t\t'Favorite'  =>  '$favorite',\n";   
    $racetext .= "\t\t'favorites' => '" . implode(", ", $favorites) . "',\n";
    $win1 = $raceData1['win'];
    $allTrioValues1 = [];
    $unionW = $win1;
    $interW = $win1;
    foreach($favorites as $F){
        $raceDataF = $history[$raceNumber][$F];
        $winF = $raceDataF['win'];
        $racetext .= "\t\t'Win values(Fav: $F)' =>  '" . implode(", ", $winF) . "',\n";
        if(!empty($winF)) $interW = array_intersect($interW, $winF);
        $unionW = array_values(array_unique(array_merge($unionW, $winF)));
    }
    //Sort  unionW by odds
    $qplsOdds = [];
    foreach($unionW as $iIndex){
       if(isset($allRacesOdds[$raceNumber][$iIndex])) $qplsOdds[$iIndex] = $allRacesOdds[$raceNumber][$iIndex];
    }
    asort($qplsOdds);
    $unionW = array_keys($qplsOdds);
    $racetext .= "\t\t'interW' =>  '" . implode(", ", $interW) . "',\n";
    $racetext .= "\t\t'unionW(count: " . count($unionW) . ")' =>  '" . implode(", ", $unionW) . "',\n";
    $racetext .= "\t],\n";
    unset($oldFavorites);
    unset($favorites);
    $outtext .= $racetext;
}

$outtext .= "];\n";

file_put_contents($outFile, $outtext);
