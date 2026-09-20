<?php

return static function (PDO $pdo, Schema $schema): void {
    if (!$schema->hasColumn('setting', 'statusagentrequest')) {
        return;
    }
    $row = $pdo->query("SELECT keyboardmain, statusagentrequest FROM `setting` LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $layout = json_decode($row['keyboardmain'] ?? '', true);
        if (is_array($layout) && isset($layout['keyboard']) && is_array($layout['keyboard'])) {
            $placed = [];
            foreach ($layout['keyboard'] as $kbRow) {
                foreach ((array) $kbRow as $button) {
                    if (is_array($button) && isset($button['text'])) {
                        $placed[] = $button['text'];
                    }
                }
            }
            $newRow = [];
            if (!in_array('text_agentpanel', $placed, true)) {
                $newRow[] = ['text' => 'text_agentpanel'];
            }
            if ($row['statusagentrequest'] !== 'offrequestagent' && !in_array('text_requestagent', $placed, true)) {
                $newRow[] = ['text' => 'text_requestagent'];
            }
            if ($newRow) {
                $layout['keyboard'][] = $newRow;
                $statement = $pdo->prepare("UPDATE `setting` SET keyboardmain = ?");
                $statement->execute([json_encode($layout, JSON_UNESCAPED_UNICODE)]);
            }
        }
    }
    $schema->dropColumn('setting', 'statusagentrequest');
};
