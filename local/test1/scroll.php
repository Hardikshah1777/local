<?php

require_once '../../config.php';

$url = new moodle_url('/local/test1/scroll.php');
$context = context_system::instance();

$PAGE->set_title("Scroll");
$PAGE->set_heading("Scroll");
$PAGE->set_url($url);
$PAGE->set_context($context);

echo $OUTPUT->header();
echo '
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
<div class="large-table-fake-top-scroll-container-3">
  <div>&nbsp;</div>
</div>
<div class="large-table-container-3">
  <table>
    <thead>      
    </thead>
    <tbody>
     <tr>
        <td>0</td>
        <td>1</td>
        <td>2</td>
        <td>3</td>
        <td>4</td>
        <td>5</td>
        <td>6</td>
        <td>7</td>
        <td>8</td>
        <td>9</td>
        <td></td>
        <td>1</td>
        <td>2</td>
        <td>3</td>
        <td>4</td>
        <td>5</td>
        <td>6</td>
        <td>7</td>
        <td>8</td>
        <td>9</td>
        <td>11</td>
        <td>13</td>
        <td>14</td>
        <td>15</td>
        <td>16</td>
        <td>17</td>
        <td>18</td>
        <td>19</td>
        <td>20</td>
      </tr>
    </tbody>
  </table>
</div>

<style>
    .large-table-container-3 {
      max-width: 200px;
      overflow-x: scroll;
      overflow-y: auto;
    }
    .large-table-container-3 table {
    }
    .large-table-fake-top-scroll-container-3 {
      max-width: 200px;
      overflow-x: scroll;
      overflow-y: auto;
    }
    .large-table-fake-top-scroll-container-3 div {
      background-color: red;
      font-size: 1px;
      line-height: 1px;
    }
</style>

<script>
    $(function () {
      var tableContainer = $(".large-table-container-3");
      var table = $(".large-table-container-3 table");
      var customContainer = $(".large-table-fake-top-scroll-container-3");
      var fakeDiv = $(".large-table-fake-top-scroll-container-3 div");
    
      var tableWidth = table.width();
      fakeDiv.width(tableWidth);
    
      customContainer.scroll(function () {
        tableContainer.scrollLeft(customContainer.scrollLeft());
      });
      tableContainer.scroll(function () {
        customContainer.scrollLeft(tableContainer.scrollLeft());
      });
    });
</script>
';
echo $OUTPUT->footer();