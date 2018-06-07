<?php

require('../vendor/autoload.php');

use Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

$dotenv = new Dotenv(__DIR__);
$dotenv->load();
$dotenv->required(['DB_HOST', 'DB_USER', 'DB_PASS', 'DB_NAME']);

$app = new Silex\Application();
$app['debug'] = getenv('DEBUG');

$conn = new mysqli(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASSWORD'), getenv('DB_NAME'));

// Register the monolog logging service
$app->register(
  new Silex\Provider\MonologServiceProvider(),
  array(
    'monolog.logfile' => 'php://stderr',
  )
);

$app->register(new JDesrosiers\Silex\Provider\CorsServiceProvider());

// Our web handlers

$app->get(
  '/words/{category}/{difficulty}/{count}',
  function ($category, $difficulty, $count) use ($app) {
    $words = file_get_contents('words.json');
    $words = json_decode($words, true);
    $_words = array_filter(
      $words,
      function ($curr) use ($category, $difficulty) {
        $wordsCount = count(explode(' ', $curr['text']));

        return $curr['categoryId'] == $category
          && $curr['difficultyLevel'] == $difficulty
          && $wordsCount <= 2;
      }
    );

    if (count($_words) == 0) {
      $_words = array_filter(
        $words,
        function ($curr) use ($category, $difficulty) {
          $wordsCount = count(explode(' ', $curr['text']));

          return $curr['categoryId'] == $category && $wordsCount <= 2;
        }
      );
    }

    shuffle($_words);

    return $app->json(array_slice($_words, 0, $count));
  }
)->value('count', 10);

$app->get(
  '/categories',
  function () use ($app, $conn) {
    $result = $conn->query('SELECT * FROM interest;');

    $categories = array_map(
      function ($curr) {
        return [
          'id' => $curr['id'],
          'title' => $curr['title_en'],
          'iconUrl' => 'http://res.cloudinary.com/vimbox/image/upload/v1496915329/profile/interest/'.$curr['icon_name'].'.png',
        ];
      },
      $result->fetch_all(MYSQLI_ASSOC)
    );

    return $app->json($categories);
  }
);

$app->post(
  '/score',
  function (Request $request) use ($app, $conn) {
    $userId = $request->get('userId');
    $name = $request->get('userName');
    $score = $request->get('score');
    $categoryId = $request->get('categoryId');

    $result = $conn->query(
      "INSERT INTO score (interest_id, user_id, `name`, score) VALUES ({$categoryId}, {$userId}, '{$name}', {$score})"
    );

    return $app->json(
      [
        'success' => !!$result,
      ]
    );
  }
);

$app->get(
  '/top',
  function () use ($app, $conn) {
    $result = $conn->query('SELECT * FROM interest;');

    foreach ($result->fetch_all(MYSQLI_ASSOC) as $category) {
      $categories[$category['id']] = $category['title_en'];
    }

    $result = $conn->query('SELECT * FROM score ORDER BY score DESC LIMIT 100;');

    $scores = array_map(
      function ($curr) use ($categories) {
        return [
          'name' => $curr['name'],
          'categoryName' => $categories[$curr['interest_id']],
          'score' => $curr['score'],
        ];
      },
      $result->fetch_all(MYSQLI_ASSOC)
    );

    return $app->json($scores);
  }
);

$app['cors-enabled']($app);
$app->run();
