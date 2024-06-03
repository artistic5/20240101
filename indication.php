<?php

if(!isset($argv[1])) die("Race Date Not Entered!!\n");

$step = "indication";
$raceDate = trim($argv[1]);
$currentDir = __DIR__ . DIRECTORY_SEPARATOR . $raceDate;

$oddsFile = $currentDir . DIRECTORY_SEPARATOR . "odds.php";
if(file_exists($oddsFile)) $allRacesOdds = include($oddsFile);
$history = include(__DIR__ . DIRECTORY_SEPARATOR . "history.php");
$inFile = $currentDir . DIRECTORY_SEPARATOR . "bets.php";
$outFile = $currentDir . DIRECTORY_SEPARATOR . "$step.php";

if(!file_exists($inFile)) exit();
$data = include($inFile);

if(file_exists($oddsFile)) $totalRaces = count($allRacesOdds);
elseif(file_exists($inFile)) $totalRaces = count($data);
else die('No input Files!');

$outtext = "<?php\n\n";
$outtext .= "return [\n";

for ($raceNumber = 1; $raceNumber <= $totalRaces; $raceNumber++) {
    $favorites = explode(", ", $data[$raceNumber]['favorites']);
    if(isset($data[$raceNumber]['official win'])) $officialWin = explode(", ", $data[$raceNumber]['official win']);
    if(isset($data[$raceNumber]['place amount'])) $placeAmount = $data[$raceNumber]['place amount'];
    $winsArray = $allRacesOdds[$raceNumber];
    asort($winsArray);
    $runners = array_keys($winsArray);
    $others = array_diff($runners, $favorites);
    sort($others);
    $racetext = "";
    $racetext .= "\t'$raceNumber' => [\n";
    $racetext .= "\t\t/**\n";
    $racetext .= "\t\tRace $raceNumber\n";
    $racetext .= "\t\t*/\n";
    $racetext .= "\t\t'favorites' => '" . implode(", ", $favorites) . "',\n"; 
    $copy = $favorites;
    if(isset($officialWin)){
        $racetext .= "\t\t'official win' => '" . implode(", ", $officialWin) . "',\n"; 
    }
    if(isset($placeAmount)){
        $racetext .= "\t\t'place amount' => [\n";
        foreach($placeAmount as $place => $amount){
            $racetext .= "\t\t\t$place => $amount,\n";
        }
        $racetext .= "\t\t],\n"; 
    }
    if(count($runners) < 10) $racetext .= "\t\t'indication' => [],\n";
    else{
        $indication = [];
        foreach($others as $other){
            $favorites = $copy;
            $favorites[] = $other;
            $sums = [];
            $mults = [];
            foreach($favorites as $one){
                foreach($favorites as $two){
                    if($two > $one){
                        $sums[] = $one + $two;
                        $mults[] = $one * $two;
                    }
                }
            }
            $sums = array_intersect($sums, $runners);
            $mults = array_intersect($mults, $runners);
            $set2 = array_values(array_unique(array_merge($sums, $mults)));
            $set2 = array_diff($set2, $favorites);
            $firstSet = true;
            foreach($favorites as $F){
                $candidates = array_intersect($history[$raceNumber][$F]["win"], $runners);
                if($firstSet) {
                    $inter = $candidates;
                    $firstSet = false;
                }
                else $inter = array_intersect($inter, $candidates);
            }
            $inter = array_intersect($favorites, $inter);
            if(count($inter) >= 2 && count($favorites) >= 3 && count($set2) < 7) $indication[] = $other;
        }
        $racetext .= "\t\t'indication' => [" . implode(", ", $indication) . "],\n";
        if(count($copy) === 2 && count($indication) >= 2) $racetext .= "\t\t//CHECK THIS ONE OUT\n";
    }
    $racetext .= "\t],\n";
    unset($favorites);
    unset($inter);
    $outtext .= $racetext;
}
$outtext .= "];\n";
file_put_contents($outFile, $outtext);
