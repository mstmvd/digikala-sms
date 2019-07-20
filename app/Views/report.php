<html>
<head>
    <title>Digikala - SMS System Report</title>
</head>
<body>
<div>
    <form target="_self" action="/report" method="post">
        <label>number:</label>
        <input type="text" id="number" name="number">
        <button type="submit">Search</button>
    </form>
</div>
<div><h2>Target Number: <?= $number ? $number : 'All' ?></h2></div>
<div><h2>Sent SMS Count: <?= $sent_sms_count ?></h2></div>
<h2>API Status</h2>
<table border="1px">
    <thead>
    <tr>
        <th>API</th>
        <th>API Use Count</th>
        <th>API Fail Ratio</th>
    </tr>
    </thead>
    <tbody>
    <?php
    foreach ($sms_apis as $i => $api) {
        $useCount = $api_use_count[$i]['total'];
        $failRatio = $api_fail_ratio[$i]['ratio'];
        echo "<tr><td>$api</td><td>$useCount</td><td>$failRatio</td></tr>";
    }
    ?>
    </tbody>
</table>
<h2>Top 10 Numbers</h2>
<table border="1px">
    <thead>
    <tr>
        <th>Rank</th>
        <th>Number</th>
        <th>Total</th>
    </tr>
    </thead>
    <tbody>
    <?php
    foreach ($top10 as $i => $t) {
        $rank = $i + 1;
        echo "<tr><td>$rank</td><td>$t[number]</td><td>$t[total]</td></tr>";
    }
    ?>
    </tbody>
</table>
</body>
</html>
