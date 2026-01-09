<?php

function getAccountBalance($conn, $user_id, $account_id) {

    $sql = "
        SELECT SUM(amount) AS balance FROM (
            -- income masuk ke account
            SELECT amount
            FROM transaction_table
            WHERE user_id = ?
              AND type = 'income'
              AND destination_account_id = ?

            UNION ALL

            -- expense keluar dari account
            SELECT -amount
            FROM transaction_table
            WHERE user_id = ?
              AND type = 'expense'
              AND source_account_id = ?

            UNION ALL

            -- transfer keluar (source)
            SELECT -amount
            FROM transaction_table
            WHERE user_id = ?
              AND type = 'transfer'
              AND source_account_id = ?

            UNION ALL

            -- transfer masuk (destination)
            SELECT amount
            FROM transaction_table
            WHERE user_id = ?
              AND type = 'transfer'
              AND destination_account_id = ?
        ) t
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "iiiiiiii",
        $user_id, $account_id,
        $user_id, $account_id,
        $user_id, $account_id,
        $user_id, $account_id
    );

    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    return $res['balance'] ?? 0;
}

function checkBudgetUsage($conn, $user_id, $category_id, $date)
{
    $month = date('m', strtotime($date)); // MATCH DB

    // total expense (bulan semasa)
    $sql_exp = "
        SELECT IFNULL(SUM(amount),0) AS total
        FROM transaction_table
        WHERE user_id = ?
          AND category_id = ?
          AND type = 'expense'
          AND MONTH(txn_date_time) = ?
    ";

    $stmt = $conn->prepare($sql_exp);
    $stmt->bind_param("iii", $user_id, $category_id, $month);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];

    // budget
    $sql_budget = "
        SELECT limit_amount
        FROM budget
        WHERE user_id = ?
          AND category_id = ?
          AND month = ?
        LIMIT 1
    ";

    $stmt2 = $conn->prepare($sql_budget);
    $stmt2->bind_param("iii", $user_id, $category_id, $month);
    $stmt2->execute();
    $res = $stmt2->get_result();

    if ($res->num_rows == 0) return null;

    $budget = $res->fetch_assoc()['limit_amount'];

    return [
        'total'   => $total,
        'budget' => $budget,
        'percent'=> ($total / $budget) * 100
    ];
}

