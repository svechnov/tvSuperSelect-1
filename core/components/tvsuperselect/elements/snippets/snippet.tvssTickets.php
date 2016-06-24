<?php

$scriptProperties['context'] = $context ?: $modx->context->key;
if (!$tags = $scriptProperties['tag'] ?: $scriptProperties['tags'] ?: $_REQUEST['tag'] ?: $_REQUEST['tags'] ?: '') {
    return false;
}
if (!$tvs = $scriptProperties['tv'] ?: $scriptProperties['tvs'] ?: '') {
    return false;
}
if (!$tvs = $scriptProperties['tv'] ?: $scriptProperties['tvs'] ?: '') {
    return false;
}
$snippet = $scriptProperties['snippet'] ?: 'getTickets';
foreach (array('tag', 'tags', 'tv', 'tvs', 'snippet') as $v) {
    unset($scriptProperties[$v]);
}

// Преобразуем список тегов в массив
if (is_string($tags)) {
    $tags = explode('||', $tags);
}
if (is_array($tags)) {
    $tags = array_map('trim', $tags);
    $tags = array_map('urldecode', $tags);
    $tags = array_diff($tags, array(''));
} else {
    return false;
}
if (empty($tags)) {
    return false;
}

// Преобразуем список ID ТВшек в массив
$tvs = explode(',', $tvs);

// Как делаем выборку, через LIKE или =
$like = isset($scriptProperties['like']) ? $scriptProperties['like'] : false;

// Подготавливаем параметры для выборки ресурсов с нужными тегами
$class = 'Ticket';
$loadModels = array('tvsuperselect' => MODX_CORE_PATH.'components/tvsuperselect/model/');
$select = array();
$leftJoin = array();
$where = array(array());

foreach ($tvs as $tv) {
    $alias = 'tvss'.$tv;
    $orConditions = array();

    foreach ($tags as $i => $tag) {
        if ($like) {
            $orConditions[] = $alias.'.value LIKE "%'.addslashes($tag).'%"';
        } else {
            $orConditions[] = $alias.'.value = "'.addslashes($tag).'"';
        }
    }

    if (!empty($orConditions)) {
        $leftJoin += array(
            $alias => array(
                'class' => 'tvssOption',
                'alias' => $alias,
                'on' => $alias.'.resource_id = '.$class.'.id AND ('.implode(' OR ', $orConditions).')',
            ),
        );
        $where[0][] = array(
            'OR:'.$alias.'.tv_id:=' => $tv,
        );
    }
}

// Приведение параметра loadModels к нужному нам виду (JSON)
if (!empty($scriptProperties['loadModels']) && !$modx->fromJSON($scriptProperties['loadModels'])) {
    $tmp_array = array_map('trim', explode(',', $scriptProperties['loadModels']));
    foreach ($tmp_array as $v) {
        $tmp[$v] = MODX_CORE_PATH.'components/'.strtolower($v).'/model/';
    }
    $scriptProperties['loadModels'] = $modx->toJSON($tmp);
}

// Обработка параметров указанных юзером, пересекающихся с параметрами сниппета
foreach (array('loadModels', 'where', 'select', 'leftJoin') as $v) {
    if (!empty($scriptProperties[$v])) {
        $tmp = $modx->fromJSON($scriptProperties[$v]);
        if (is_array($tmp)) {
            $$v = array_merge($$v, $tmp);
        }
    }
    unset($scriptProperties[$v]);
}

// Сливаем подготоваленные параметры с указанными юзером и запускаем
$output = $modx->runSnippet($snippet, array_merge(array(
        'class' => $class,
        'loadModels' => $modx->toJSON($loadModels),
        'select' => $modx->toJSON($select),
        'leftJoin' => $modx->toJSON($leftJoin),
        'where' => $modx->toJSON($where),
        'groupby' => $class.'.id',
    ),
    $scriptProperties
));

return $output;