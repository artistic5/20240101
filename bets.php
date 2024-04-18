<?php

if(!isset($argv[1])) die("Race Date Not Entered!!\n");

$step = "bets";
$raceDate = trim($argv[1]);
$currentDir = __DIR__ . DIRECTORY_SEPARATOR . $raceDate;

$allRacesOdds = include($currentDir . DIRECTORY_SEPARATOR . "odds.php");
$history = include(__DIR__ . DIRECTORY_SEPARATOR . "winhistory.php");
$matrix = include(__DIR__ . DIRECTORY_SEPARATOR . "matrix.php");
$outFile = $currentDir . DIRECTORY_SEPARATOR . "$step.php";

if(file_exists($outFile)){
    $oldData = include($outFile);
}

$totalRaces = count($allRacesOdds);

$outtext = "<?php\n\n";
$outtext .= "return [\n";
for ($raceNumber = 1; $raceNumber <= $totalRaces; $raceNumber++) {
    if(!isset($allRacesOdds[$raceNumber])) continue;
    if(isset($oldData)){
        if(isset($oldData[$raceNumber]['favorites'])) $oldFavorites = explode(", ", $oldData[$raceNumber]['favorites']);
        if(isset($oldData[$raceNumber]['official win'])) $officialWin = explode(", ", $oldData[$raceNumber]['official win']);
    }
    if(isset($oldFavorites)) $favorites = $oldFavorites;
    else $favorites = [];
    $winsArray = $allRacesOdds[$raceNumber];
    asort($winsArray);
    $runners = array_keys($winsArray);
    $favorite = $runners[0];
    if(!in_array($favorite, $favorites)) $favorites[] = $favorite;
    sort($runners);
    sort($favorites);
    $racetext = "";
    $racetext .= "\t'$raceNumber' => [\n";
    $racetext .= "\t\t/**\n";
    $racetext .= "\t\tRace $raceNumber\n";
    $racetext .= "\t\t*/\n";
    $racetext .= "\t\t'favorites' => '" . implode(", ", $favorites) . "',\n"; 
   
    if(isset($officialWin)){
        $racetext .= "\t\t'official win' => '" . implode(", ", $officialWin) . "',\n"; 
    }
   
    $union = [];
    $L = [];
    $R = [];
    $golden = [];
    foreach($favorites as $F){
        $win = array_intersect($history[$raceNumber][$F]['win'], $runners);
        $union = array_values(array_unique(array_merge($union, $win)));
        foreach($union as $bo){
            if(isset($matrix[$raceNumber][$F][$bo])){
                if($matrix[$raceNumber][$F][$bo] === true){
                    if($F % 2 === 0 && $bo == 2) $golden[] = $bo;
                    if($F % 3 === 0 && $bo == 3) $golden[] = $bo;
                    $racetext .= "\t\t'Favs $F, $bo' => 'true',\n"; 
                    if(!in_array($F, $L)) $L[] = $F;
                    if(!in_array($bo, $R)) $R[] = $bo;
                }
            }
        }
    }
    sort($union);
    $racetext .= "\t\t'win hist' => '" . implode(", ", $union) . "',//count: " . count($union) . "\n"; 
    $shit = array_values(array_unique(array_merge($L, $R)));
    sort($shit);
    if(!empty($shit)){
        $racetext .= "\t\t'shit' => '" . implode(", ", $shit) . "',\n"; 
    }
    if(!empty($golden) && count($favorites) >= 2){
        $racetext .= "\t\t'gold' => '" . implode(", ", $golden) . "',\n"; 
    }
    $racetext .= "\t],\n";
    unset($oldFavorites);
    unset($favorites);
    $outtext .= $racetext;
}
$outtext .= "];\n";
file_put_contents($outFile, $outtext);