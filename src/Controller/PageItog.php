<?php

namespace Drupal\report\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

/**
 * Controller routines for page example routes.
 */
class PageItog extends ControllerBase {

  /**
   * A more complex _controller callback that takes arguments.
   */
  public function report($start, $end) {
    $st = strtotime($start);
    $en = strtotime($end);
    if ($st == 0 || $en == 0 || $st > $en) {
      return [
        '#markup' => 'Введите корректные даты отчетного периода.',
      ];
    }
    $visitor = $this->getVisitor($start, $end);
    $activity = [];
    $all_programm = [];
    $vsego_programm = 0;
    $vsego_people = 0;

    foreach ($visitor as $key => $node) {
      $activity = $node->field_visitor_ref_activity->entity;
      $activity_id = $activity->id();
      $visitor_id = $node->id();

      if (!isset($all_programm[$activity_id])) {
        $all_programm[$activity_id] = [
          'title' => $activity->title->value,
          'long_time' => $activity->field_activity_long_time->value,
          'kolvo_people' => 0,
          'summa' => 0,
        ];
      }

      // Считаем количество проведенных программ.
      $kolvo_programm = 1;
      $current_summa = $all_programm[$activity_id]['summa'];
      $new_summa = $current_summa + $kolvo_programm;
      $all_programm[$activity_id]['summa'] = $new_summa;
      $vsego_programm = $vsego_programm + $kolvo_programm;

      // Считаем количество человек
      $kolvo_people = $node->field_visitor_people->value;
      $current_kolvo_people = (int) $all_programm[$activity_id]['kolvo_people'];
      $new_kolvo_people = $current_kolvo_people + $kolvo_people;
      $all_programm[$activity_id]['kolvo_people'] = $new_kolvo_people;
      $vsego_people = $vsego_people + $kolvo_people;
    }

    // А вот и сам массив, данные из которого мы выводим на странице.
    $renderable = [];
    $renderable['info'] = [
      '#markup' => "Отчет с " . format_date(strtotime($start), 'custom', 'd-m-Y')
      . " по " . format_date(strtotime($end), 'custom', 'd-m-Y'),
    ];
    sort($all_programm);
    $data = [
      'all_programm' => $all_programm,
      'vsego_programm' => $vsego_programm,
      'vsego_people' => $vsego_people,
    ];
    $renderable['h'] = [
      '#theme' => 'report',
      '#data' => $data,
    ];
    return $renderable;
  }

  /**
   * Функциями getХххх() формируем из ноды объекты.
   */
  public function getVisitor($start, $end) {
    $start = strtotime($start);
    $end = strtotime($end);
    $start_norm = format_date($start, 'custom', "Y-m-d");
    $end_norm = format_date($end, 'custom', "Y-m-d");
    $query = \Drupal::entityQuery('node');
    $query->condition('status', 1);
    $query->condition('type', 'visitor');
    $query->condition('field_visitor_date', $start_norm, '>=');
    $query->condition('field_visitor_date', $end_norm, '<=');
    $entity_ids = $query->execute();
    $visitor = Node::loadMultiple($entity_ids);
    return $visitor;
  }

}
