<?php
function createDeck() {
    $suits  = ['H', 'D', 'C', 'S'];
    $values = ['2','3','4','5','6','7','8','9','10','J','Q','K','A'];
    $deck   = [];
    foreach ($suits as $s) {
        foreach ($values as $v) {
            $deck[] = $v . $s;
        }
    }
    shuffle($deck);
    return $deck;
}

function cardValue($card) {
    $val = substr($card, 0, strlen($card) - 1);
    $map = ['2'=>2,'3'=>3,'4'=>4,'5'=>5,'6'=>6,'7'=>7,'8'=>8,
            '9'=>9,'10'=>10,'J'=>11,'Q'=>12,'K'=>13,'A'=>14];
    return $map[$val] ?? 0;
}

function cardSuit($card) {
    return substr($card, -1);
}

function startGame($pdo, $room_id, $players) {
    $deck = createDeck();

    foreach ($players as $player) {
        $hand = [array_pop($deck), array_pop($deck)];
        $stmt = $pdo->prepare("UPDATE players_in_room SET hand=?, status='active', current_bet=0, total_bet=0 WHERE id=?");
        $stmt->execute([json_encode($hand), $player['id']]);
    }

    $small_blind = 10;
    $big_blind   = 20;

    postBlind($pdo, $players[0], $small_blind);
    postBlind($pdo, $players[1], $big_blind);

    $first = count($players) > 2 ? $players[2]['user_id'] : $players[0]['user_id'];

    $stmt = $pdo->prepare("
        INSERT INTO game_state (room_id, deck, community_cards, pot, current_player, round, current_bet, dealer_seat)
        VALUES (?, ?, '[]', ?, ?, 'preflop', ?, 0)
        ON DUPLICATE KEY UPDATE
            deck=VALUES(deck), community_cards='[]', pot=VALUES(pot),
            current_player=VALUES(current_player), round='preflop',
            current_bet=VALUES(current_bet), dealer_seat=0, winner_hand=NULL
    ");
    $stmt->execute([$room_id, json_encode($deck), $small_blind + $big_blind, $first, $big_blind]);

    $stmt = $pdo->prepare("UPDATE rooms SET status='playing' WHERE id=?");
    $stmt->execute([$room_id]);
}

function postBlind($pdo, $player, $amount) {
    $actual = min($amount, $player['chips']);
    $stmt = $pdo->prepare("UPDATE players_in_room SET chips=chips-?, current_bet=?, total_bet=? WHERE id=?");
    $stmt->execute([$actual, $actual, $actual, $player['id']]);
}

function handleAction($pdo, $room_id, $user_id, $action, $amount = 0) {
    $stmt = $pdo->prepare("SELECT * FROM game_state WHERE room_id=?");
    $stmt->execute([$room_id]);
    $state = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$state || $state['current_player'] != $user_id) {
        return ['success' => false, 'message' => 'Det är inte din tur'];
    }

    $stmt = $pdo->prepare("SELECT p.*, u.username FROM players_in_room p JOIN users u ON p.user_id=u.id WHERE p.room_id=? ORDER BY p.seat");
    $stmt->execute([$room_id]);
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $currentPlayer = null;
    foreach ($players as $p) {
        if ($p['user_id'] == $user_id) { $currentPlayer = $p; break; }
    }
    if (!$currentPlayer) return ['success' => false, 'message' => 'Spelare hittades inte'];

    $call_amount = $state['current_bet'] - $currentPlayer['current_bet'];

    if ($action === 'fold') {
        $stmt = $pdo->prepare("UPDATE players_in_room SET status='folded' WHERE id=?");
        $stmt->execute([$currentPlayer['id']]);

    } elseif ($action === 'check') {
        if ($call_amount > 0) {
            return ['success' => false, 'message' => 'Du kan inte checka – calla eller folda'];
        }

    } elseif ($action === 'call') {
        $actual  = min($call_amount, $currentPlayer['chips']);
        $new_bet = $currentPlayer['current_bet'] + $actual;
        $stmt = $pdo->prepare("UPDATE players_in_room SET chips=chips-?, current_bet=?, total_bet=total_bet+? WHERE id=?");
        $stmt->execute([$actual, $new_bet, $actual, $currentPlayer['id']]);
        $stmt = $pdo->prepare("UPDATE game_state SET pot=pot+? WHERE room_id=?");
        $stmt->execute([$actual, $room_id]);
        if ($currentPlayer['chips'] - $actual <= 0) {
            $stmt = $pdo->prepare("UPDATE players_in_room SET status='all-in' WHERE id=?");
            $stmt->execute([$currentPlayer['id']]);
        }

    } elseif ($action === 'raise') {
        $min_raise = $state['current_bet'] * 2;
        $raise_to  = max($amount, $min_raise);
        $extra     = $raise_to - $currentPlayer['current_bet'];
        $actual    = min($extra, $currentPlayer['chips']);
        $new_bet   = $currentPlayer['current_bet'] + $actual;
        $stmt = $pdo->prepare("UPDATE players_in_room SET chips=chips-?, current_bet=?, total_bet=total_bet+? WHERE id=?");
        $stmt->execute([$actual, $new_bet, $actual, $currentPlayer['id']]);
        $stmt = $pdo->prepare("UPDATE game_state SET pot=pot+?, current_bet=? WHERE room_id=?");
        $stmt->execute([$actual, $new_bet, $room_id]);
    }

    advanceTurn($pdo, $room_id, $currentPlayer);
    return ['success' => true];
}

function advanceTurn($pdo, $room_id, $currentPlayer) {
    $stmt = $pdo->prepare("SELECT * FROM game_state WHERE room_id=?");
    $stmt->execute([$room_id]);
    $state = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT p.*, u.username FROM players_in_room p JOIN users u ON p.user_id=u.id WHERE p.room_id=? ORDER BY p.seat");
    $stmt->execute([$room_id]);
    $allPlayers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $nonFolded = array_values(array_filter($allPlayers, fn($p) => !in_array($p['status'], ['folded','out'])));
    if (count($nonFolded) === 1) {
        endHand($pdo, $room_id, [$nonFolded[0]], 'Alla andra lade sig');
        return;
    }

    $activePlayers = array_values(array_filter($allPlayers, fn($p) => $p['status'] === 'active'));

    $allCalled = true;
    foreach ($activePlayers as $p) {
        if ($p['current_bet'] < $state['current_bet']) {
            $allCalled = false;
            break;
        }
    }

    if ($allCalled || count($activePlayers) === 0) {
        nextRound($pdo, $room_id, $state, $allPlayers);
        return;
    }

    $currentSeat = $currentPlayer['seat'];
    $nextPlayer  = null;
    foreach ($activePlayers as $p) {
        if ($p['seat'] > $currentSeat) { $nextPlayer = $p; break; }
    }
    if (!$nextPlayer) $nextPlayer = $activePlayers[0];

    $stmt = $pdo->prepare("UPDATE game_state SET current_player=? WHERE room_id=?");
    $stmt->execute([$nextPlayer['user_id'], $room_id]);
}

function nextRound($pdo, $room_id, $state, $players) {
    $deck      = json_decode($state['deck'], true);
    $community = json_decode($state['community_cards'], true) ?: [];

    $stmt = $pdo->prepare("UPDATE players_in_room SET current_bet=0 WHERE room_id=?");
    $stmt->execute([$room_id]);

    switch ($state['round']) {
        case 'preflop':
            $community[] = array_pop($deck);
            $community[] = array_pop($deck);
            $community[] = array_pop($deck);
            $nextRound = 'flop';
            break;
        case 'flop':
            $community[] = array_pop($deck);
            $nextRound = 'turn';
            break;
        case 'turn':
            $community[] = array_pop($deck);
            $nextRound = 'river';
            break;
        case 'river':
            showdown($pdo, $room_id, $players, $community, $state);
            return;
        default:
            return;
    }

    $active = array_values(array_filter($players, fn($p) => $p['status'] === 'active'));
    usort($active, fn($a, $b) => $a['seat'] - $b['seat']);
    $firstPlayer = $active[0];

    $stmt = $pdo->prepare("UPDATE game_state SET deck=?, community_cards=?, round=?, current_player=?, current_bet=0 WHERE room_id=?");
    $stmt->execute([json_encode($deck), json_encode($community), $nextRound, $firstPlayer['user_id'], $room_id]);
}

function showdown($pdo, $room_id, $players, $community, $state) {
    $inPlay    = array_filter($players, fn($p) => in_array($p['status'], ['active','all-in']));
    $best_rank = -1;
    $winners   = [];
    $hand_name = '';

    foreach ($inPlay as $player) {
        $hand = json_decode($player['hand'], true);
        if (!$hand) continue;
        $all_cards = array_merge($hand, $community);
        [$rank, $name] = evaluateHand($all_cards);
        if ($rank > $best_rank) {
            $best_rank = $rank;
            $winners   = [$player];
            $hand_name = $name;
        } elseif ($rank === $best_rank) {
            $winners[] = $player;
        }
    }
    endHand($pdo, $room_id, $winners, $hand_name);
}

function endHand($pdo, $room_id, $winners, $hand_name) {
    $stmt = $pdo->prepare("SELECT pot FROM game_state WHERE room_id=?");
    $stmt->execute([$room_id]);
    $pot   = intval($stmt->fetchColumn());
    $split = count($winners) > 0 ? intval($pot / count($winners)) : 0;

    foreach ($winners as $w) {
        $stmt = $pdo->prepare("UPDATE players_in_room SET chips=chips+? WHERE id=?");
        $stmt->execute([$split, $w['id']]);
        $stmt = $pdo->prepare("UPDATE users u JOIN players_in_room p ON p.user_id=u.id SET u.chips=p.chips WHERE p.id=?");
        $stmt->execute([$w['id']]);
    }

    $names = implode(' & ', array_map(fn($w) => $w['username'], $winners));
    $msg   = $names . ' vann med ' . $hand_name;

    $stmt = $pdo->prepare("UPDATE game_state SET round='showdown', current_player=NULL, winner_hand=? WHERE room_id=?");
    $stmt->execute([$msg, $room_id]);
    $stmt = $pdo->prepare("UPDATE rooms SET status='finished' WHERE id=?");
    $stmt->execute([$room_id]);
}

function evaluateHand($cards) {
    $best   = [0, 'Högsta kort'];
    $combos = getCombinations($cards, 5);
    foreach ($combos as $hand) {
        $result = evaluate5($hand);
        if ($result[0] > $best[0]) $best = $result;
    }
    return $best;
}

function getCombinations($arr, $k) {
    if ($k === 0) return [[]];
    if (empty($arr)) return [];
    $first        = array_shift($arr);
    $withFirst    = array_map(fn($c) => array_merge([$first], $c), getCombinations($arr, $k - 1));
    $withoutFirst = getCombinations($arr, $k);
    return array_merge($withFirst, $withoutFirst);
}

function evaluate5($hand) {
    $values = array_map('cardValue', $hand);
    $suits  = array_map('cardSuit', $hand);
    sort($values);

    $isFlush    = count(array_unique($suits)) === 1;
    $uniq       = array_unique($values);
    sort($uniq);
    $isStraight = (count($uniq) === 5 && ($uniq[4] - $uniq[0] === 4));
    if ($uniq === [2,3,4,5,14]) $isStraight = true;

    $counts = array_count_values($values);
    arsort($counts);
    $groups = array_values($counts);

    if ($isFlush && $isStraight) {
        if (in_array(14,$values) && in_array(13,$values)) return [9, 'Royal Flush'];
        return [8, 'Straight Flush'];
    }
    if ($groups[0] === 4) return [7, 'Fyrtal'];
    if ($groups[0] === 3 && ($groups[1] ?? 0) === 2) return [6, 'Kåk'];
    if ($isFlush) return [5, 'Färg'];
    if ($isStraight) return [4, 'Stege'];
    if ($groups[0] === 3) return [3, 'Triss'];
    if ($groups[0] === 2 && ($groups[1] ?? 0) === 2) return [2, 'Två par'];
    if ($groups[0] === 2) return [1, 'Ett par'];
    return [0, 'Högsta kort'];
}
?>