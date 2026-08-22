<?php
/**
 * Relatorio de distribuicoes de chamados.
 *
 * @author Fabio Neres
 * @license GPLv3+
 */

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}

PluginAtribuicaointeligenteConfig::assertCanView();

global $DB;

if (!function_exists('plugin_atribuicaointeligente_distribution_escape')) {
   function plugin_atribuicaointeligente_distribution_escape($value): string {
      return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_decode_label')) {
   function plugin_atribuicaointeligente_distribution_decode_label($value): string {
      $label = (string) $value;
      for ($i = 0; $i < 2; $i++) {
         $decoded = html_entity_decode($label, ENT_QUOTES, 'UTF-8');
         if ($decoded === $label) {
            break;
         }
         $label = $decoded;
      }

      return $label;
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_entity_label')) {
   function plugin_atribuicaointeligente_distribution_entity_label($entitiesId): string {
      $entitiesId = (int) $entitiesId;
      if ($entitiesId <= 0) {
         return __('Todas / global', 'atribuicaointeligente');
      }

      $label = Dropdown::getDropdownName('glpi_entities', $entitiesId);
      return plugin_atribuicaointeligente_distribution_decode_label($label !== '' ? $label : $entitiesId);
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_date')) {
   function plugin_atribuicaointeligente_distribution_date(string $value): string {
      return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_filter_fields')) {
   function plugin_atribuicaointeligente_distribution_filter_fields(): array {
      return [
         'date_start',
         'date_end',
         'entities_id',
         'itilcategories_id',
         'action_type',
         'distribution_source',
         'source',
         'chart_categories',
         'chart_summary_distributors',
         'chart_daily',
         'chart_top_distributors',
         'chart_actuation',
         'chart_top_technicians',
         'chart_transfers',
         'evolution_period',
         'chart_gradient',
         'chart_show_labels',
         'chart_data_limit',
         'chart_background',
         'chart_color_categories',
         'chart_color_summary_distributors',
         'chart_color_daily',
         'chart_color_top_distributors',
         'chart_color_actuation',
         'chart_color_top_technicians',
         'chart_color_transfers',
      ];
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_category_label')) {
   function plugin_atribuicaointeligente_distribution_category_label($categoriesId): string {
      $categoriesId = (int) $categoriesId;
      if ($categoriesId <= 0) {
         return __('Sem categoria', 'atribuicaointeligente');
      }

      $label = Dropdown::getDropdownName('glpi_itilcategories', $categoriesId);
      return plugin_atribuicaointeligente_distribution_decode_label($label !== '' ? $label : $categoriesId);
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_chart_types')) {
   function plugin_atribuicaointeligente_distribution_chart_types(): array {
      return [
         'pie'            => __('Pizza', 'atribuicaointeligente'),
         'donut'          => __('Rosca', 'atribuicaointeligente'),
         'half_pie'       => __('Meia torta', 'atribuicaointeligente'),
         'half_donut'     => __('Meia rosquinha', 'atribuicaointeligente'),
         'bar_vertical'   => __('Barras', 'atribuicaointeligente'),
         'bar_horizontal' => __('Barras horizontais', 'atribuicaointeligente'),
         'numbers'        => __('Múltiplos números', 'atribuicaointeligente'),
         'summary_number' => __('Números de resumo', 'atribuicaointeligente'),
         'table'          => __('Tabela', 'atribuicaointeligente'),
      ];
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_chart_type')) {
   function plugin_atribuicaointeligente_distribution_chart_type(array $input, string $field, string $default): string {
      $value = (string) ($input[$field] ?? $default);
      return array_key_exists($value, plugin_atribuicaointeligente_distribution_chart_types()) ? $value : $default;
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_bool_filter')) {
   function plugin_atribuicaointeligente_distribution_bool_filter(array $input, string $field, int $default = 0): int {
      if (!array_key_exists($field, $input)) {
         return $default;
      }

      return !empty($input[$field]) ? 1 : 0;
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_data_limit')) {
   function plugin_atribuicaointeligente_distribution_data_limit(array $input): int {
      $limit = (int) ($input['chart_data_limit'] ?? 10);
      return max(1, min(50, $limit));
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_color')) {
   function plugin_atribuicaointeligente_distribution_color(array $input, string $field, string $default): string {
      $value = trim((string) ($input[$field] ?? $default));
      return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? strtolower($value) : $default;
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_optional_color')) {
   function plugin_atribuicaointeligente_distribution_optional_color(array $input, string $field): string {
      $value = trim((string) ($input[$field] ?? ''));
      return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? strtolower($value) : '';
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_soft_color')) {
   function plugin_atribuicaointeligente_distribution_soft_color(string $color): string {
      if (preg_match('/^#([0-9a-fA-F]{6})$/', $color, $matches) !== 1) {
         return '#f1f5f9';
      }

      $hex = $matches[1];
      $red = hexdec(substr($hex, 0, 2));
      $green = hexdec(substr($hex, 2, 2));
      $blue = hexdec(substr($hex, 4, 2));
      $mix = 0.84;

      $red = (int) round(($red * (1 - $mix)) + (255 * $mix));
      $green = (int) round(($green * (1 - $mix)) + (255 * $mix));
      $blue = (int) round(($blue * (1 - $mix)) + (255 * $mix));

      return sprintf('#%02x%02x%02x', $red, $green, $blue);
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_periods')) {
   function plugin_atribuicaointeligente_distribution_periods(): array {
      return [
         'day'   => __('Dia', 'atribuicaointeligente'),
         'month' => __('Mes', 'atribuicaointeligente'),
         'year'  => __('Ano', 'atribuicaointeligente'),
      ];
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_period')) {
   function plugin_atribuicaointeligente_distribution_period(array $input): string {
      $value = (string) ($input['evolution_period'] ?? 'day');
      return array_key_exists($value, plugin_atribuicaointeligente_distribution_periods()) ? $value : 'day';
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_has_request_filters')) {
   function plugin_atribuicaointeligente_distribution_has_request_filters(): bool {
      if (isset($_GET['distribution_filter'])) {
         return true;
      }

      foreach (plugin_atribuicaointeligente_distribution_filter_fields() as $field) {
         if (array_key_exists($field, $_GET)) {
            return true;
         }
      }

      return false;
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_normalize_filters')) {
   function plugin_atribuicaointeligente_distribution_normalize_filters(array $input): array {
      $distributionSource = (string) ($input['distribution_source'] ?? ($input['source'] ?? ''));

      $filters = [
         'date_start'        => plugin_atribuicaointeligente_distribution_date((string) ($input['date_start'] ?? date('Y-m-01'))),
         'date_end'          => plugin_atribuicaointeligente_distribution_date((string) ($input['date_end'] ?? date('Y-m-d'))),
         'entities_id'       => array_key_exists('entities_id', $input) && $input['entities_id'] !== '' ? max(0, (int) $input['entities_id']) : '',
         'itilcategories_id' => max(0, (int) ($input['itilcategories_id'] ?? 0)),
         'action_type'       => (string) ($input['action_type'] ?? ''),
         'source'            => $distributionSource,
         'chart_categories'      => plugin_atribuicaointeligente_distribution_chart_type($input, 'chart_categories', (string) ($input['chart_technicians'] ?? 'bar_horizontal')),
         'chart_summary_distributors' => plugin_atribuicaointeligente_distribution_chart_type($input, 'chart_summary_distributors', 'table'),
         'chart_daily'           => plugin_atribuicaointeligente_distribution_chart_type($input, 'chart_daily', 'bar_vertical'),
         'chart_top_distributors'=> plugin_atribuicaointeligente_distribution_chart_type($input, 'chart_top_distributors', 'bar_horizontal'),
         'chart_actuation'       => plugin_atribuicaointeligente_distribution_chart_type($input, 'chart_actuation', 'bar_horizontal'),
         'chart_top_technicians' => plugin_atribuicaointeligente_distribution_chart_type($input, 'chart_top_technicians', 'bar_horizontal'),
         'chart_transfers'       => plugin_atribuicaointeligente_distribution_chart_type($input, 'chart_transfers', 'bar_horizontal'),
         'evolution_period'      => plugin_atribuicaointeligente_distribution_period($input),
         'chart_gradient'        => plugin_atribuicaointeligente_distribution_bool_filter($input, 'chart_gradient'),
         'chart_show_labels'     => plugin_atribuicaointeligente_distribution_bool_filter($input, 'chart_show_labels', 1),
         'chart_data_limit'      => plugin_atribuicaointeligente_distribution_data_limit($input),
         'chart_background'      => plugin_atribuicaointeligente_distribution_color($input, 'chart_background', '#fafafa'),
         'chart_color_categories'      => plugin_atribuicaointeligente_distribution_optional_color($input, 'chart_color_categories'),
         'chart_color_summary_distributors' => plugin_atribuicaointeligente_distribution_optional_color($input, 'chart_color_summary_distributors'),
         'chart_color_daily'           => plugin_atribuicaointeligente_distribution_optional_color($input, 'chart_color_daily'),
         'chart_color_top_distributors'=> plugin_atribuicaointeligente_distribution_optional_color($input, 'chart_color_top_distributors'),
         'chart_color_actuation'       => plugin_atribuicaointeligente_distribution_optional_color($input, 'chart_color_actuation'),
         'chart_color_top_technicians' => plugin_atribuicaointeligente_distribution_optional_color($input, 'chart_color_top_technicians'),
         'chart_color_transfers'       => plugin_atribuicaointeligente_distribution_optional_color($input, 'chart_color_transfers'),
      ];

      if (!in_array($filters['action_type'], array_merge([''], PluginAtribuicaointeligenteDistributionLog::getAllowedActions()), true)) {
         $filters['action_type'] = '';
      }
      if (!in_array($filters['source'], array_merge([''], PluginAtribuicaointeligenteDistributionLog::getAllowedSources()), true)) {
         $filters['source'] = '';
      }

      return $filters;
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_filter_url')) {
   function plugin_atribuicaointeligente_distribution_filter_url(bool $embedded, array $filters): string {
      $target = $embedded
         ? PluginAtribuicaointeligenteConfig::getFormURL(true)
         : $_SERVER['PHP_SELF'];

      if ($embedded) {
         $filters['forcetab'] = 'PluginAtribuicaointeligenteConfig$5';
      }

      return $target . '?' . http_build_query($filters);
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_has_filter')) {
   function plugin_atribuicaointeligente_distribution_has_filter(array $filters, string $field): bool {
      return array_key_exists($field, $filters)
         && $filters[$field] !== ''
         && $filters[$field] !== null;
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_entity_dropdown')) {
   function plugin_atribuicaointeligente_distribution_entity_dropdown(string $name, $value): void {
      global $DB;

      echo '<select class="form-select" name="' . plugin_atribuicaointeligente_distribution_escape($name) . '">';
      echo '<option value="">' . plugin_atribuicaointeligente_distribution_escape(__('Todas', 'atribuicaointeligente')) . '</option>';

      if ($DB->tableExists('glpi_entities')) {
         $result = $DB->doQuery('SELECT `id`, `completename`, `name` FROM `glpi_entities` ORDER BY `completename` ASC, `name` ASC, `id` ASC');
         while ($result && ($row = $result->fetch_assoc())) {
            $entityId = (int) ($row['id'] ?? 0);
            $entityName = (string) ($row['completename'] ?? '');
            if ($entityName === '') {
               $entityName = (string) ($row['name'] ?? $entityId);
            }
            $entityName = plugin_atribuicaointeligente_distribution_decode_label($entityName);
            $selected = $value !== '' && (int) $value === $entityId ? ' selected' : '';
            echo '<option value="' . $entityId . '"' . $selected . '>' . plugin_atribuicaointeligente_distribution_escape($entityName) . '</option>';
         }
      }

      echo '</select>';
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_fetch_rows')) {
   function plugin_atribuicaointeligente_distribution_fetch_rows(string $sql): array {
      global $DB;

      $rows = [];
      $result = $DB->doQuery($sql);
      while ($result && ($row = $result->fetch_assoc())) {
         $rows[] = $row;
      }

      return $rows;
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_max')) {
   function plugin_atribuicaointeligente_distribution_max(array $rows, string $field): int {
      $max = 0;
      foreach ($rows as $row) {
         $max = max($max, (int) ($row[$field] ?? 0));
      }

      return $max > 0 ? $max : 1;
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_palette')) {
   function plugin_atribuicaointeligente_distribution_palette(): array {
      return [
         ['#2563eb', '#dbeafe', '#0ea5e9'],
         ['#16a34a', '#dcfce7', '#84cc16'],
         ['#f59e0b', '#fef3c7', '#ef4444'],
         ['#7c3aed', '#ede9fe', '#ec4899'],
         ['#0891b2', '#cffafe', '#14b8a6'],
         ['#dc2626', '#fee2e2', '#f97316'],
         ['#4f46e5', '#e0e7ff', '#06b6d4'],
         ['#9333ea', '#f3e8ff', '#db2777'],
      ];
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_chart_colors')) {
   function plugin_atribuicaointeligente_distribution_chart_colors(string $label, int $index): array {
      $normalized = strtolower(html_entity_decode(strip_tags($label), ENT_QUOTES, 'UTF-8'));
      if (strpos($normalized, 'integral') !== false) {
         return ['#16a34a', '#dcfce7', '#84cc16'];
      }
      if (strpos($normalized, 'parcial') !== false) {
         return ['#f59e0b', '#fef3c7', '#f59e0b'];
      }
      if (strpos($normalized, 'manual') !== false) {
         return ['#dc2626', '#fee2e2', '#dc2626'];
      }
      if (strpos($normalized, 'transfer') !== false) {
         return ['#7c3aed', '#ede9fe', '#0ea5e9'];
      }

      $palette = plugin_atribuicaointeligente_distribution_palette();
      return $palette[$index % count($palette)];
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_bar')) {
   function plugin_atribuicaointeligente_distribution_bar(int $value, int $max): string {
      $width = $max > 0 ? max(4, min(100, (int) round(($value / $max) * 100))) : 0;

      return '<div class="progress ai-distribution-progress">'
         . '<div class="progress-bar" role="progressbar" style="width: ' . $width . '%;" aria-valuenow="' . $value . '" aria-valuemin="0" aria-valuemax="' . $max . '"></div>'
         . '</div>';
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_chart_bar')) {
   function plugin_atribuicaointeligente_distribution_chart_bar(int $value, int $max, string $type, array $filters): string {
      $showLabels = !empty($filters['chart_show_labels']);
      $gradientClass = !empty($filters['chart_gradient']) ? ' ai-distribution-chart-gradient' : '';

      if ($type === 'table') {
         return '<span class="ai-distribution-chart-value">' . $value . '</span>';
      }

      if ($type === 'bar_vertical') {
         $height = $max > 0 ? max(4, min(100, (int) round(($value / $max) * 100))) : 0;

         return '<div class="ai-distribution-chart-vertical' . $gradientClass . '">'
            . ($showLabels ? '<span class="ai-distribution-chart-value">' . $value . '</span>' : '')
            . '<div class="ai-distribution-chart-column">'
            . '<span style="height: ' . $height . '%;" aria-label="' . $value . '"></span>'
            . '</div>'
            . '</div>';
      }

      if (in_array($type, ['pie', 'donut', 'half_pie', 'half_donut'], true)) {
         $degrees = $max > 0 ? max(4, min(360, (int) round(($value / $max) * 360))) : 0;
         $class = 'ai-distribution-chart-pie ai-distribution-chart-' . $type . $gradientClass;

         return '<div class="' . $class . '" style="--ai-chart-degrees: ' . $degrees . 'deg;">'
            . ($showLabels ? '<span class="ai-distribution-chart-value">' . $value . '</span>' : '')
            . '</div>';
      }

      if ($type === 'numbers' || $type === 'summary_number') {
         return '<span class="ai-distribution-chart-value ai-distribution-chart-number">' . $value . '</span>';
      }

      return '<div class="ai-distribution-chart-horizontal' . $gradientClass . '">'
         . plugin_atribuicaointeligente_distribution_bar($value, $max)
         . ($showLabels ? '<span class="ai-distribution-chart-value">' . $value . '</span>' : '')
         . '</div>';
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_is_pie_chart')) {
   function plugin_atribuicaointeligente_distribution_is_pie_chart(string $chartType): bool {
      return in_array($chartType, ['pie', 'donut', 'half_pie', 'half_donut'], true);
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_render_pie_widget')) {
   function plugin_atribuicaointeligente_distribution_render_pie_widget(
      array $rows,
      string $labelField,
      string $valueField,
      string $chartType,
      array $filters,
      callable $labelCallback = null,
      string $colorField = ''
   ): void {
      $total = 0;
      $items = [];
      $customColor = $colorField !== '' ? (string) ($filters[$colorField] ?? '') : '';
      $customSoft = $customColor !== '' ? plugin_atribuicaointeligente_distribution_soft_color($customColor) : '';

      foreach ($rows as $index => $row) {
         $value = max(0, (int) ($row[$valueField] ?? 0));
         if ($value <= 0) {
            continue;
         }

         $label = $labelCallback !== null ? (string) $labelCallback($row) : (string) ($row[$labelField] ?? '');
         $colors = plugin_atribuicaointeligente_distribution_chart_colors($label, (int) $index);
         if ($customColor !== '' && empty($items)) {
            $colors = [$customColor, $customSoft, $customColor];
         }
         $items[] = [
            'label' => $label,
            'value' => $value,
            'color' => !empty($filters['chart_gradient']) ? $colors[2] : $colors[0],
            'soft'  => $colors[1],
         ];
         $total += $value;
      }

      if ($total <= 0 || empty($items)) {
         echo '<div class="ai-distribution-empty">'
            . '<span>' . plugin_atribuicaointeligente_distribution_escape(__('Nenhum registro encontrado.', 'atribuicaointeligente')) . '</span>'
            . '</div>';
         return;
      }

      $background = plugin_atribuicaointeligente_distribution_escape($filters['chart_background'] ?? '#fafafa');
      $segments = [];
      $start = 0.0;
      $lastIndex = count($items) - 1;
      foreach ($items as $index => $item) {
         $end = $index === $lastIndex ? 360.0 : $start + (($item['value'] / $total) * 360.0);
         $segments[] = $item['color'] . ' ' . round($start, 2) . 'deg ' . round($end, 2) . 'deg';
         $start = $end;
      }

      $class = 'ai-distribution-pie-summary ai-distribution-pie-summary-' . preg_replace('/[^a-z0-9_-]/', '', $chartType);
      $gradient = 'conic-gradient(' . implode(', ', $segments) . ')';

      echo '<div class="' . $class . '" style="--ai-chart-background: ' . $background . '; --ai-pie-gradient: ' . plugin_atribuicaointeligente_distribution_escape($gradient) . ';">';
      echo '<div class="ai-distribution-pie-visual">';
      echo '<div class="ai-distribution-pie-shape">';
      if (!empty($filters['chart_show_labels'])) {
         echo '<span class="ai-distribution-pie-total">' . (int) $total . '</span>';
      }
      echo '</div>';
      echo '</div>';
      echo '<div class="ai-distribution-pie-legend">';
      foreach ($items as $item) {
         $percent = $total > 0 ? (int) round(($item['value'] / $total) * 100) : 0;
         echo '<div class="ai-distribution-pie-legend-item" style="--ai-chart-color: ' . plugin_atribuicaointeligente_distribution_escape($item['color']) . '; --ai-chart-soft: ' . plugin_atribuicaointeligente_distribution_escape($item['soft']) . ';">';
         echo '<span class="ai-distribution-pie-dot"></span>';
         echo '<span class="ai-distribution-pie-label">' . plugin_atribuicaointeligente_distribution_escape($item['label']) . '</span>';
         echo '<span class="ai-distribution-pie-metric">' . (int) $item['value'] . ' · ' . $percent . '%</span>';
         echo '</div>';
      }
      echo '</div>';
      echo '</div>';
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_render_widget')) {
   function plugin_atribuicaointeligente_distribution_render_widget(
      array $rows,
      string $labelField,
      string $valueField,
      string $chartType,
      array $filters,
      callable $labelCallback = null,
      string $emptyColspan = '3',
      string $colorField = ''
   ): void {
      if (empty($rows)) {
         echo '<div class="ai-distribution-empty">'
            . '<span>' . plugin_atribuicaointeligente_distribution_escape(__('Nenhum registro encontrado.', 'atribuicaointeligente')) . '</span>'
            . '</div>';
         return;
      }

      if (plugin_atribuicaointeligente_distribution_is_pie_chart($chartType)) {
         plugin_atribuicaointeligente_distribution_render_pie_widget(
            $rows,
            $labelField,
            $valueField,
            $chartType,
            $filters,
            $labelCallback,
            $colorField
         );
         return;
      }

      $max = plugin_atribuicaointeligente_distribution_max($rows, $valueField);
      $background = plugin_atribuicaointeligente_distribution_escape($filters['chart_background'] ?? '#fafafa');
      $customColor = $colorField !== '' ? (string) ($filters[$colorField] ?? '') : '';
      $customSoft = $customColor !== '' ? plugin_atribuicaointeligente_distribution_soft_color($customColor) : '';
      $typeClass = 'ai-distribution-widget-' . preg_replace('/[^a-z0-9_-]/', '', $chartType);
      echo '<div class="ai-distribution-widget ' . $typeClass . '" style="--ai-chart-background: ' . $background . ';">';
      foreach ($rows as $index => $row) {
         $value = (int) ($row[$valueField] ?? 0);
         $label = $labelCallback !== null ? (string) $labelCallback($row) : (string) ($row[$labelField] ?? '');
         $colors = plugin_atribuicaointeligente_distribution_chart_colors($label, (int) $index);
         if ($customColor !== '') {
            $colors = [$customColor, $customSoft, $customColor];
         }
         $style = '--ai-chart-color: ' . $colors[0] . '; --ai-chart-soft: ' . $colors[1] . '; --ai-chart-color-2: ' . $colors[2] . ';';
         echo '<div class="ai-distribution-widget-item" style="' . plugin_atribuicaointeligente_distribution_escape($style) . '" title="' . plugin_atribuicaointeligente_distribution_escape($label . ': ' . $value) . '">';
         echo '<div class="ai-distribution-widget-label">' . plugin_atribuicaointeligente_distribution_escape($label) . '</div>';
         echo '<div class="ai-distribution-widget-value">'
            . plugin_atribuicaointeligente_distribution_chart_bar($value, $max, $chartType, $filters)
            . '</div>';
         echo '</div>';
      }
      echo '</div>';
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_chart_dropdown')) {
   function plugin_atribuicaointeligente_distribution_chart_dropdown(string $name, array $filters, string $label): void {
      echo '<div class="col-12 col-md-2">';
      echo '<label class="form-label" for="' . plugin_atribuicaointeligente_distribution_escape($name) . '">'
         . plugin_atribuicaointeligente_distribution_escape($label)
         . '</label>';
      echo '<select class="form-select" id="' . plugin_atribuicaointeligente_distribution_escape($name) . '" name="' . plugin_atribuicaointeligente_distribution_escape($name) . '">';
      foreach (plugin_atribuicaointeligente_distribution_chart_types() as $value => $text) {
         $selected = ($filters[$name] ?? '') === $value ? ' selected' : '';
         echo '<option value="' . plugin_atribuicaointeligente_distribution_escape($value) . '"' . $selected . '>'
            . plugin_atribuicaointeligente_distribution_escape($text)
            . '</option>';
      }
      echo '</select>';
      echo '</div>';
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_chart_color_input')) {
   function plugin_atribuicaointeligente_distribution_chart_color_input(string $name, array $filters, string $label): void {
      echo '<div class="col-12 col-md-2">';
      echo '<label class="form-label" for="' . plugin_atribuicaointeligente_distribution_escape($name) . '">'
         . plugin_atribuicaointeligente_distribution_escape($label)
         . '</label>';
      echo '<input class="form-control" type="text" id="' . plugin_atribuicaointeligente_distribution_escape($name) . '" name="' . plugin_atribuicaointeligente_distribution_escape($name) . '" value="'
         . plugin_atribuicaointeligente_distribution_escape($filters[$name] ?? '')
         . '" placeholder="#2563eb">';
      echo '</div>';
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_user_entity_condition')) {
   function plugin_atribuicaointeligente_distribution_user_entity_condition(string $userSql, array $filters, bool $allowSystem = false): string {
      $userSql = trim($userSql);
      if ($userSql === '' || !plugin_atribuicaointeligente_distribution_has_filter($filters, 'entities_id')) {
         return '1 = 1';
      }

      $entityId = (int) $filters['entities_id'];
      $userProfileCondition = "{$userSql} > 0
         AND (
            NOT EXISTS (
               SELECT 1
               FROM `glpi_profiles_users` pu_any
               WHERE pu_any.`users_id` = {$userSql}
            )
            OR EXISTS (
               SELECT 1
               FROM `glpi_profiles_users` pu
               LEFT JOIN `glpi_entities` selected_entity
                  ON selected_entity.`id` = {$entityId}
               WHERE pu.`users_id` = {$userSql}
                 AND (
                    pu.`entities_id` = {$entityId}
                    OR (
                       pu.`is_recursive` = 1
                       AND (
                          pu.`entities_id` = 0
                          OR selected_entity.`ancestors_cache` LIKE CONCAT('%\"', pu.`entities_id`, '\"%')
                          OR selected_entity.`ancestors_cache` LIKE CONCAT('%i:', pu.`entities_id`, ';%')
                       )
                    )
                 )
            )
         )";

      if ($allowSystem) {
         return "({$userSql} = 0 OR ({$userProfileCondition}))";
      }

      return $userProfileCondition;
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_where')) {
   function plugin_atribuicaointeligente_distribution_where(array $filters, string $alias = ''): string {
      $clauses = ['1 = 1'];
      $prefix = $alias !== '' ? '`' . preg_replace('/[^a-zA-Z0-9_]/', '', $alias) . '`.' : '';

      if (!Session::canViewAllEntities()) {
         $entities = array_map('intval', $_SESSION['glpiactiveentities'] ?? []);
         $entities[] = 0;
         $entities = array_values(array_unique(array_filter($entities, static function($entityId) {
            return $entityId >= 0;
         })));
         if (empty($entities)) {
            $entities = [0];
         }
         $clauses[] = $prefix . '`entities_id` IN (' . implode(',', $entities) . ')';
      }

      if ($filters['date_start'] !== '') {
         $clauses[] = $prefix . "`date_creation` >= '" . addslashes($filters['date_start']) . " 00:00:00'";
      }
      if ($filters['date_end'] !== '') {
         $clauses[] = $prefix . "`date_creation` <= '" . addslashes($filters['date_end']) . " 23:59:59'";
      }

      if ((int) ($filters['itilcategories_id'] ?? 0) > 0) {
         $clauses[] = $prefix . "`itilcategories_id` = " . (int) $filters['itilcategories_id'];
      }
      if (plugin_atribuicaointeligente_distribution_has_filter($filters, 'entities_id')) {
         $entityId = (int) $filters['entities_id'];
         $clauses[] = '('
            . $prefix . "`entities_id` = {$entityId}"
            . ' OR ' . $prefix . "`entities_id_from` = {$entityId}"
            . ' OR ' . $prefix . "`entities_id_to` = {$entityId}"
            . ')';
      }

      if ($filters['action_type'] !== '') {
         $clauses[] = $prefix . "`action_type` = '" . addslashes($filters['action_type']) . "'";
      }
      if ($filters['source'] !== '') {
         $clauses[] = $prefix . "`source` = '" . addslashes($filters['source']) . "'";
      }

      return implode(' AND ', $clauses);
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_decision_log_where')) {
   function plugin_atribuicaointeligente_distribution_decision_log_where(array $filters, string $alias = 'decisionlog'): string {
      $alias = preg_replace('/[^a-zA-Z0-9_]/', '', $alias);
      $prefix = '`' . $alias . '`.';
      $clauses = [
         $prefix . "`selected_users_id` IS NOT NULL",
         $prefix . "`tickets_id` IS NOT NULL",
         $prefix . "`tickets_id` > 0",
         $prefix . "`reason` IN ('Tecnico atribuido automaticamente', 'Tecnico atribuido automaticamente apos atualizacao do chamado')",
      ];

      if (!Session::canViewAllEntities()) {
         $entities = array_map('intval', $_SESSION['glpiactiveentities'] ?? []);
         $entities[] = 0;
         $entities = array_values(array_unique(array_filter($entities, static function($entityId) {
            return $entityId >= 0;
         })));
         if (empty($entities)) {
            $entities = [0];
         }
         $clauses[] = $prefix . '`entities_id` IN (' . implode(',', $entities) . ')';
      }
      if ($filters['date_start'] !== '') {
         $clauses[] = $prefix . "`date_creation` >= '" . addslashes($filters['date_start']) . " 00:00:00'";
      }
      if ($filters['date_end'] !== '') {
         $clauses[] = $prefix . "`date_creation` <= '" . addslashes($filters['date_end']) . " 23:59:59'";
      }
      if (plugin_atribuicaointeligente_distribution_has_filter($filters, 'entities_id')) {
         $clauses[] = $prefix . "`entities_id` = " . (int) $filters['entities_id'];
      }
      if ((int) ($filters['itilcategories_id'] ?? 0) > 0) {
         $clauses[] = $prefix . "`itilcategories_id` = " . (int) $filters['itilcategories_id'];
      }
      if (($filters['action_type'] ?? '') !== ''
         && $filters['action_type'] !== PluginAtribuicaointeligenteDistributionLog::ACTION_TECHNICIAN_ASSIGNED
      ) {
         $clauses[] = '1 = 0';
      }
      if (($filters['source'] ?? '') === PluginAtribuicaointeligenteDistributionLog::SOURCE_MANUAL) {
         $clauses[] = '1 = 0';
      }

      return implode(' AND ', $clauses);
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_decision_technician_where')) {
   function plugin_atribuicaointeligente_distribution_decision_technician_where(array $filters, string $alias = 'decisionlog'): string {
      $alias = preg_replace('/[^a-zA-Z0-9_]/', '', $alias);
      $prefix = '`' . $alias . '`.';
      $clauses = [
         $prefix . "`selected_users_id` IS NOT NULL",
         $prefix . "`selected_users_id` > 0",
         $prefix . "`tickets_id` IS NOT NULL",
         $prefix . "`tickets_id` > 0",
      ];

      if (!Session::canViewAllEntities()) {
         $entities = array_map('intval', $_SESSION['glpiactiveentities'] ?? []);
         $entities[] = 0;
         $entities = array_values(array_unique(array_filter($entities, static function($entityId) {
            return $entityId >= 0;
         })));
         if (empty($entities)) {
            $entities = [0];
         }
         $clauses[] = $prefix . '`entities_id` IN (' . implode(',', $entities) . ')';
      }
      if ($filters['date_start'] !== '') {
         $clauses[] = $prefix . "`date_creation` >= '" . addslashes($filters['date_start']) . " 00:00:00'";
      }
      if ($filters['date_end'] !== '') {
         $clauses[] = $prefix . "`date_creation` <= '" . addslashes($filters['date_end']) . " 23:59:59'";
      }
      if (plugin_atribuicaointeligente_distribution_has_filter($filters, 'entities_id')) {
         $clauses[] = $prefix . "`entities_id` = " . (int) $filters['entities_id'];
      }
      if ((int) ($filters['itilcategories_id'] ?? 0) > 0) {
         $clauses[] = $prefix . "`itilcategories_id` = " . (int) $filters['itilcategories_id'];
      }
      if (($filters['action_type'] ?? '') !== ''
         && $filters['action_type'] !== PluginAtribuicaointeligenteDistributionLog::ACTION_TECHNICIAN_ASSIGNED
      ) {
         $clauses[] = '1 = 0';
      }
      if (($filters['source'] ?? '') === PluginAtribuicaointeligenteDistributionLog::SOURCE_MANUAL) {
         $clauses[] = '1 = 0';
      }

      return implode(' AND ', $clauses);
   }
}

$embedded = !empty($_GET['embedded']);
$table = PluginAtribuicaointeligenteDistributionLog::getTable();

$filterSessionKey = 'plugin_atribuicaointeligente_distribution_filters';
if (!empty($_GET['distribution_clear'])) {
   unset($_SESSION[$filterSessionKey]);
}

if (empty($_GET['distribution_clear']) && plugin_atribuicaointeligente_distribution_has_request_filters()) {
   $filters = plugin_atribuicaointeligente_distribution_normalize_filters($_GET);
   $_SESSION[$filterSessionKey] = $filters;
} elseif (isset($_SESSION[$filterSessionKey]) && is_array($_SESSION[$filterSessionKey])) {
   $filters = plugin_atribuicaointeligente_distribution_normalize_filters($_SESSION[$filterSessionKey]);
} else {
   $filters = plugin_atribuicaointeligente_distribution_normalize_filters([]);
}

PluginAtribuicaointeligenteConfig::ensureDistributionLogSchema();

$whereSql = plugin_atribuicaointeligente_distribution_where($filters);
$chartDataLimit = (int) $filters['chart_data_limit'];
$actorEntitySql = plugin_atribuicaointeligente_distribution_user_entity_condition('`users_id_actor`', $filters, true);
$technicianEntitySql = plugin_atribuicaointeligente_distribution_user_entity_condition('technician_summary.`users_id_to`', $filters);
$technicianDirectEntitySql = plugin_atribuicaointeligente_distribution_user_entity_condition('`users_id_to`', $filters);
$summaryRows = [];
$topDistributorRows = [];
$categoryRows = [];
$technicianRows = [];
$topTechnicianRows = [];
$dailyRows = [];
$actuationRows = [
   'plugin_only'  => [
      'label'        => __('Automação integral', 'atribuicaointeligente'),
      'tickets_count' => 0,
      'total_events'  => 0,
   ],
   'plugin_human' => [
      'label'        => __('Automação parcial', 'atribuicaointeligente'),
      'tickets_count' => 0,
      'total_events'  => 0,
   ],
   'human_only'   => [
      'label'        => __('Atuação manual', 'atribuicaointeligente'),
      'tickets_count' => 0,
      'total_events'  => 0,
   ],
];
$transferRows = [];
$distinctTickets = 0;
$transferTickets = 0;

if ($DB->tableExists($table)) {
   $decisionTable = PluginAtribuicaointeligenteConfig::getDecisionLogsTable();
   $periodSql = "DATE(`date_creation`)";
   $periodAliasSql = "DATE(dl.`date_creation`)";
   if ($filters['evolution_period'] === 'month') {
      $periodSql = "DATE_FORMAT(`date_creation`, '%Y-%m')";
      $periodAliasSql = "DATE_FORMAT(dl.`date_creation`, '%Y-%m')";
   } else if ($filters['evolution_period'] === 'year') {
      $periodSql = "DATE_FORMAT(`date_creation`, '%Y')";
      $periodAliasSql = "DATE_FORMAT(dl.`date_creation`, '%Y')";
   }

   $countResult = $DB->doQuery(
      "SELECT COUNT(DISTINCT `tickets_id`) AS distinct_tickets
       FROM `{$table}`
       WHERE {$whereSql}"
   );
   if ($countResult && ($countRow = $countResult->fetch_assoc())) {
      $distinctTickets = (int) ($countRow['distinct_tickets'] ?? 0);
   }
   if ($DB->tableExists($decisionTable)) {
      $decisionLogWhereSql = plugin_atribuicaointeligente_distribution_decision_log_where($filters, 'decisionlog');
      $decisionCountRows = plugin_atribuicaointeligente_distribution_fetch_rows(
         "SELECT COUNT(DISTINCT report_tickets.`tickets_id`) AS distinct_tickets
          FROM (
             SELECT `tickets_id`
             FROM `{$table}`
             WHERE {$whereSql}
               AND `tickets_id` > 0
             UNION
             SELECT decisionlog.`tickets_id`
             FROM `{$decisionTable}` decisionlog
             WHERE {$decisionLogWhereSql}
          ) report_tickets"
      );
      if (!empty($decisionCountRows)) {
         $distinctTickets = (int) ($decisionCountRows[0]['distinct_tickets'] ?? $distinctTickets);
      }
   }

   $summaryResult = $DB->doQuery(
      "SELECT `users_id_actor`,
              COUNT(DISTINCT CASE WHEN `action_type` = '" . PluginAtribuicaointeligenteDistributionLog::ACTION_ENTITY_TRANSFERRED . "' THEN `tickets_id` ELSE NULL END) AS transfer_tickets,
              COUNT(DISTINCT `tickets_id`) AS tickets_count
       FROM `{$table}`
       WHERE {$whereSql}
         AND {$actorEntitySql}
       GROUP BY `users_id_actor`
       ORDER BY tickets_count DESC, transfer_tickets DESC, `users_id_actor` ASC
       LIMIT {$chartDataLimit}"
   );
   if ($summaryResult) {
      while ($row = $summaryResult->fetch_assoc()) {
         $summaryRows[] = $row;
      }
   }

   $topDistributorRows = plugin_atribuicaointeligente_distribution_fetch_rows(
      "SELECT `users_id_actor`,
              COUNT(DISTINCT `tickets_id`) AS tickets_count
       FROM `{$table}`
       WHERE {$whereSql}
         AND {$actorEntitySql}
       GROUP BY `users_id_actor`
       ORDER BY tickets_count DESC, `users_id_actor` ASC
       LIMIT {$chartDataLimit}"
   );

    $dailyRows = plugin_atribuicaointeligente_distribution_fetch_rows(
       "SELECT {$periodSql} AS distribution_period,
              COUNT(DISTINCT `tickets_id`) AS tickets_count
       FROM `{$table}`
       WHERE {$whereSql}
       GROUP BY {$periodSql}
       ORDER BY distribution_period ASC
       LIMIT 120"
   );

    if ($DB->tableExists($decisionTable)) {
       $whereSqlAliased = plugin_atribuicaointeligente_distribution_where($filters, 'dl');
      $decisionLogWhereSql = plugin_atribuicaointeligente_distribution_decision_log_where($filters, 'decisionlog');
      $decisionTechnicianWhereSql = plugin_atribuicaointeligente_distribution_decision_technician_where($filters, 'decisionlog');

      $decisionPeriodSql = str_replace('dl.', 'decisionlog.', $periodAliasSql);
      $dailyRows = plugin_atribuicaointeligente_distribution_fetch_rows(
         "SELECT period_summary.`distribution_period`,
                 COUNT(DISTINCT period_summary.`tickets_id`) AS tickets_count
          FROM (
             SELECT dl.`tickets_id`,
                    {$periodAliasSql} AS distribution_period
             FROM `{$table}` dl
             WHERE {$whereSqlAliased}
               AND dl.`tickets_id` > 0
             UNION ALL
             SELECT decisionlog.`tickets_id`,
                    {$decisionPeriodSql} AS distribution_period
             FROM `{$decisionTable}` decisionlog
             WHERE {$decisionLogWhereSql}
          ) period_summary
          GROUP BY period_summary.`distribution_period`
          ORDER BY period_summary.`distribution_period` ASC
          LIMIT 120"
      );

      $categoryRows = plugin_atribuicaointeligente_distribution_fetch_rows(
         "SELECT category_summary.`itilcategories_id`,
                 COUNT(DISTINCT category_summary.`tickets_id`) AS tickets_count
          FROM (
             SELECT dl.`tickets_id`,
                    COALESCE(dl.`itilcategories_id`, 0) AS itilcategories_id
             FROM `{$table}` dl
             WHERE {$whereSqlAliased}
             UNION ALL
             SELECT decisionlog.`tickets_id`,
                    COALESCE(decisionlog.`itilcategories_id`, 0) AS itilcategories_id
             FROM `{$decisionTable}` decisionlog
             WHERE {$decisionLogWhereSql}
          ) category_summary
          GROUP BY category_summary.`itilcategories_id`
          ORDER BY tickets_count DESC, category_summary.`itilcategories_id` ASC
          LIMIT {$chartDataLimit}"
   );

      $technicianRows = plugin_atribuicaointeligente_distribution_fetch_rows(
         "SELECT technician_summary.`users_id_to`,
                 COUNT(DISTINCT technician_summary.`tickets_id`) AS tickets_count
          FROM (
             SELECT dl.`tickets_id`,
                    dl.`users_id_to`
             FROM `{$table}` dl
             WHERE {$whereSqlAliased}
               AND dl.`action_type` = '" . PluginAtribuicaointeligenteDistributionLog::ACTION_TECHNICIAN_ASSIGNED . "'
               AND dl.`users_id_to` IS NOT NULL
               AND dl.`users_id_to` > 0
             UNION ALL
              SELECT decisionlog.`tickets_id`,
                     decisionlog.`selected_users_id` AS users_id_to
              FROM `{$decisionTable}` decisionlog
              WHERE {$decisionTechnicianWhereSql}
          ) technician_summary
          WHERE {$technicianEntitySql}
          GROUP BY technician_summary.`users_id_to`
          ORDER BY tickets_count DESC, technician_summary.`users_id_to` ASC
          LIMIT {$chartDataLimit}"
   );
      $topTechnicianRows = array_slice($technicianRows, 0, $chartDataLimit);

      $actuationRawRows = plugin_atribuicaointeligente_distribution_fetch_rows(
         "SELECT ticket_classification.`classification`,
                 COUNT(*) AS tickets_count,
                 SUM(ticket_classification.`total_events`) AS total_events
          FROM (
             SELECT ticket_summary.`tickets_id`,
                     ticket_summary.`total_events`,
                     CASE
                       WHEN ticket_summary.`manual_clear_events` > 0
                            AND (ticket_summary.`plugin_events` = 0
                                 OR ticket_summary.`last_manual_clear_date` >= ticket_summary.`last_plugin_date`)
                           THEN 'human_only'
                       WHEN ticket_summary.`plugin_update_events` > 0
                          THEN 'plugin_human'
                       WHEN ticket_summary.`plugin_only_events` > 0
                          THEN 'plugin_only'
                       WHEN ticket_summary.`manual_clear_events` > 0
                          THEN 'human_only'
                        ELSE 'human_only'
                     END AS classification
               FROM (
                  SELECT ticket_events.`tickets_id`,
                         SUM(ticket_events.`total_events`) AS total_events,
                         SUM(ticket_events.`plugin_events`) AS plugin_events,
                         SUM(ticket_events.`plugin_only_events`) AS plugin_only_events,
                         SUM(ticket_events.`plugin_update_events`) AS plugin_update_events,
                         SUM(ticket_events.`manual_clear_events`) AS manual_clear_events,
                         MAX(ticket_events.`last_plugin_date`) AS last_plugin_date,
                         MAX(ticket_events.`last_manual_clear_date`) AS last_manual_clear_date
                  FROM (
                     SELECT dl.`tickets_id`,
                            COUNT(*) AS total_events,
                            0 AS plugin_events,
                            0 AS plugin_only_events,
                            0 AS plugin_update_events,
                            SUM(CASE WHEN dl.`source` = '" . PluginAtribuicaointeligenteDistributionLog::SOURCE_MANUAL . "'
                                       AND dl.`action_type` = '" . PluginAtribuicaointeligenteDistributionLog::ACTION_TECHNICIAN_ASSIGNED . "'
                                      THEN 1
                                     WHEN dl.`action_type` = '" . PluginAtribuicaointeligenteDistributionLog::ACTION_ENTITY_TRANSFERRED . "'
                                     THEN 1 ELSE 0 END) AS manual_technician_events,
                            SUM(CASE WHEN dl.`source` = '" . PluginAtribuicaointeligenteDistributionLog::SOURCE_MANUAL . "'
                                       AND dl.`action_type` = '" . PluginAtribuicaointeligenteDistributionLog::ACTION_TECHNICIAN_ASSIGNED . "'
                                      THEN 1
                                     WHEN dl.`action_type` = '" . PluginAtribuicaointeligenteDistributionLog::ACTION_ENTITY_TRANSFERRED . "'
                                      THEN 1 ELSE 0 END) AS manual_clear_events,
                            NULL AS last_plugin_date,
                            MAX(CASE WHEN dl.`source` = '" . PluginAtribuicaointeligenteDistributionLog::SOURCE_MANUAL . "'
                                       AND dl.`action_type` = '" . PluginAtribuicaointeligenteDistributionLog::ACTION_TECHNICIAN_ASSIGNED . "'
                                      THEN dl.`date_creation`
                                     WHEN dl.`action_type` = '" . PluginAtribuicaointeligenteDistributionLog::ACTION_ENTITY_TRANSFERRED . "'
                                      THEN dl.`date_creation` ELSE NULL END) AS last_manual_clear_date
                     FROM `{$table}` dl
                     WHERE {$whereSqlAliased}
                     GROUP BY dl.`tickets_id`
                     UNION ALL
                     SELECT decisionlog.`tickets_id`,
                            COUNT(*) AS total_events,
                            SUM(CASE WHEN decisionlog.`reason` IN ('Tecnico atribuido automaticamente', 'Tecnico atribuido automaticamente apos atualizacao do chamado') THEN 1 ELSE 0 END) AS plugin_events,
                            SUM(CASE WHEN decisionlog.`reason` = 'Tecnico atribuido automaticamente' THEN 1 ELSE 0 END) AS plugin_only_events,
                            SUM(CASE WHEN decisionlog.`reason` = 'Tecnico atribuido automaticamente apos atualizacao do chamado' THEN 1 ELSE 0 END) AS plugin_update_events,
                            0 AS manual_technician_events,
                            0 AS manual_clear_events,
                            MAX(CASE WHEN decisionlog.`reason` IN ('Tecnico atribuido automaticamente', 'Tecnico atribuido automaticamente apos atualizacao do chamado') THEN decisionlog.`date_creation` ELSE NULL END) AS last_plugin_date,
                            NULL AS last_manual_clear_date
                     FROM `{$decisionTable}` decisionlog
                     WHERE {$decisionLogWhereSql}
                     GROUP BY decisionlog.`tickets_id`
                 ) ticket_events
                 GROUP BY ticket_events.`tickets_id`
              ) ticket_summary
           ) ticket_classification
           GROUP BY ticket_classification.`classification`"
      );

      foreach ($actuationRawRows as $row) {
         $classification = (string) ($row['classification'] ?? '');
         if (isset($actuationRows[$classification])) {
            $actuationRows[$classification]['tickets_count'] = (int) ($row['tickets_count'] ?? 0);
            $actuationRows[$classification]['total_events'] = (int) ($row['total_events'] ?? 0);
         }
      }
      uasort($actuationRows, static function (array $left, array $right): int {
         $ticketDiff = (int) ($right['tickets_count'] ?? 0) <=> (int) ($left['tickets_count'] ?? 0);
         if ($ticketDiff !== 0) {
            return $ticketDiff;
         }

         return (int) ($right['total_events'] ?? 0) <=> (int) ($left['total_events'] ?? 0);
      });
   } else {
      $categoryRows = plugin_atribuicaointeligente_distribution_fetch_rows(
         "SELECT COALESCE(`itilcategories_id`, 0) AS itilcategories_id,
                 COUNT(DISTINCT `tickets_id`) AS tickets_count
          FROM `{$table}`
          WHERE {$whereSql}
          GROUP BY COALESCE(`itilcategories_id`, 0)
          ORDER BY tickets_count DESC, itilcategories_id ASC
          LIMIT {$chartDataLimit}"
   );

      $technicianRows = plugin_atribuicaointeligente_distribution_fetch_rows(
         "SELECT `users_id_to`,
                 COUNT(DISTINCT `tickets_id`) AS tickets_count
          FROM `{$table}`
          WHERE {$whereSql}
            AND `action_type` = '" . PluginAtribuicaointeligenteDistributionLog::ACTION_TECHNICIAN_ASSIGNED . "'
            AND `users_id_to` IS NOT NULL
            AND {$technicianDirectEntitySql}
          GROUP BY `users_id_to`
          ORDER BY tickets_count DESC, `users_id_to` ASC
          LIMIT {$chartDataLimit}"
   );
      $topTechnicianRows = array_slice($technicianRows, 0, $chartDataLimit);
   }

   $transferRows = plugin_atribuicaointeligente_distribution_fetch_rows(
      "SELECT `entities_id_from`,
              `entities_id_to`,
              COUNT(DISTINCT `tickets_id`) AS tickets_count
       FROM `{$table}`
       WHERE {$whereSql}
         AND `action_type` = '" . PluginAtribuicaointeligenteDistributionLog::ACTION_ENTITY_TRANSFERRED . "'
       GROUP BY `entities_id_from`, `entities_id_to`
       ORDER BY tickets_count DESC, `entities_id_from` ASC, `entities_id_to` ASC
       LIMIT {$chartDataLimit}"
   );
   $transferCountRows = plugin_atribuicaointeligente_distribution_fetch_rows(
      "SELECT COUNT(DISTINCT `tickets_id`) AS tickets_count
       FROM `{$table}`
       WHERE {$whereSql}
         AND `action_type` = '" . PluginAtribuicaointeligenteDistributionLog::ACTION_ENTITY_TRANSFERRED . "'"
   );
   if (!empty($transferCountRows)) {
      $transferTickets = (int) ($transferCountRows[0]['tickets_count'] ?? 0);
   }
}

$automationIntegralTickets = (int) ($actuationRows['plugin_only']['tickets_count'] ?? 0);
$automationPartialTickets = (int) ($actuationRows['plugin_human']['tickets_count'] ?? 0);
$manualTickets = (int) ($actuationRows['human_only']['tickets_count'] ?? 0);
$automationTickets = $automationIntegralTickets + $automationPartialTickets;
$automationRate = $distinctTickets > 0 ? (int) round(($automationTickets / $distinctTickets) * 100) : 0;
$transferRate = $distinctTickets > 0 ? (int) round(($transferTickets / $distinctTickets) * 100) : 0;

if (!$embedded) {
   Html::header(
      PluginAtribuicaointeligenteDistributionLog::getTypeName(Session::getPluralNumber()),
      $_SERVER['PHP_SELF'],
      'plugins',
      PluginAtribuicaointeligenteConfig::class
   );
}

$formAction = $embedded ? PluginAtribuicaointeligenteConfig::getFormURL(true) : $_SERVER['PHP_SELF'];
?>

<style>
   .ai-distribution-page {
      --ai-card-radius: 8px;
      --ai-soft-border: rgba(15, 23, 42, 0.08);
      --ai-muted: #64748b;
   }
   .ai-distribution-title {
      align-items: center;
      display: flex;
      gap: 0.65rem;
      margin-bottom: 1rem;
   }
   .ai-distribution-title-icon {
      align-items: center;
      background: #e0f2fe;
      border-radius: 8px;
      color: #0369a1;
      display: inline-flex;
      height: 2rem;
      justify-content: center;
      width: 2rem;
   }
   .ai-distribution-filter-card,
   .ai-distribution-card,
   .ai-distribution-kpi {
      border: 1px solid var(--ai-soft-border);
      border-radius: var(--ai-card-radius);
      box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
   }
   .ai-distribution-card {
      container-type: inline-size;
   }
   .ai-distribution-card .card-header {
      background: linear-gradient(180deg, rgba(248, 250, 252, 0.92), rgba(255, 255, 255, 0.96));
      border-bottom: 1px solid var(--ai-soft-border);
      min-height: 3.15rem;
   }
   .ai-distribution-kpi {
      background:
         linear-gradient(135deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.92)),
         linear-gradient(135deg, var(--ai-kpi-soft, #dbeafe), #ffffff);
      border-left: 4px solid var(--ai-kpi-color, #2563eb);
      height: 100%;
      overflow: hidden;
      position: relative;
   }
   .ai-distribution-kpi::after {
      background: var(--ai-kpi-soft, #dbeafe);
      border-radius: 999px;
      content: "";
      height: 4rem;
      opacity: 0.55;
      position: absolute;
      right: -1.7rem;
      top: -1.9rem;
      width: 4rem;
   }
   .ai-distribution-kpi .card-body {
      position: relative;
      z-index: 1;
   }
   .ai-distribution-kpi-label {
      color: var(--ai-muted);
      font-size: 0.75rem;
      font-weight: 700;
      letter-spacing: 0.02em;
      text-transform: uppercase;
   }
   .ai-distribution-kpi-value {
      color: #0f172a;
      font-size: 1.85rem;
      font-weight: 800;
      line-height: 1.1;
      margin-top: 0.2rem;
   }
   .ai-distribution-kpi-caption {
      color: var(--ai-muted);
      font-size: 0.78rem;
      margin-top: 0.35rem;
   }
   .ai-distribution-progress {
      background: var(--ai-chart-soft, #eef1f4);
      border-radius: 999px;
      height: 0.8rem;
      overflow: hidden;
   }
   .ai-distribution-chart-horizontal {
      align-items: center;
      display: grid;
      gap: 0.5rem;
      grid-template-columns: minmax(7rem, 1fr) auto;
   }
   .ai-distribution-chart-value {
      color: #0f172a;
      font-weight: 600;
      line-height: 1;
      white-space: nowrap;
   }
   .ai-distribution-chart-vertical {
      align-items: flex-end;
      display: flex;
      flex-direction: column;
      gap: 0.25rem;
      height: 3.5rem;
      min-width: 5rem;
   }
   .ai-distribution-chart-column {
      align-items: flex-end;
      background: var(--ai-chart-soft, #eef1f4);
      border-radius: 4px;
      display: flex;
      height: 100%;
      overflow: hidden;
      width: 1.6rem;
   }
   .ai-distribution-chart-column span {
      background: var(--ai-chart-color, var(--tblr-primary, #467fcf));
      display: block;
      width: 100%;
   }
   .ai-distribution-progress .progress-bar {
      background: var(--ai-chart-color, var(--tblr-primary, #467fcf));
   }
   .ai-distribution-chart-gradient .progress-bar,
   .ai-distribution-chart-gradient .ai-distribution-chart-column span {
      background-image: linear-gradient(135deg, var(--ai-chart-color, #467fcf), var(--ai-chart-color-2, #ffc857));
   }
   .ai-distribution-chart-pie {
      align-items: center;
      aspect-ratio: 1;
      background: conic-gradient(var(--ai-chart-color, var(--tblr-primary, #467fcf)) 0 var(--ai-chart-degrees), var(--ai-chart-soft, #eef1f4) var(--ai-chart-degrees) 360deg);
      border-radius: 50%;
      display: inline-flex;
      height: 3.75rem;
      justify-content: center;
      min-width: 3.75rem;
   }
   .ai-distribution-chart-donut,
   .ai-distribution-chart-half_donut {
      background:
         radial-gradient(circle at center, var(--ai-chart-background, #fafafa) 0 46%, transparent 47%),
         conic-gradient(var(--ai-chart-color, var(--tblr-primary, #467fcf)) 0 var(--ai-chart-degrees), var(--ai-chart-soft, #eef1f4) var(--ai-chart-degrees) 360deg);
   }
   .ai-distribution-chart-half_pie,
   .ai-distribution-chart-half_donut {
      clip-path: inset(0 0 50% 0);
      transform: translateY(25%);
   }
   .ai-distribution-chart-gradient.ai-distribution-chart-pie {
      background: conic-gradient(var(--ai-chart-color, #467fcf) 0 var(--ai-chart-degrees), var(--ai-chart-color-2, #ffc857) var(--ai-chart-degrees), var(--ai-chart-soft, #eef1f4) var(--ai-chart-degrees) 360deg);
   }
   .ai-distribution-chart-pie .ai-distribution-chart-value {
      align-items: center;
      background: rgba(255, 255, 255, 0.82);
      border-radius: 50%;
      display: inline-flex;
      height: 2rem;
      justify-content: center;
      min-width: 2rem;
      padding: 0 0.25rem;
   }
   .ai-distribution-chart-number {
      display: inline-block;
      font-size: 1.5rem;
      min-width: 3rem;
   }
   .ai-distribution-widget {
      background: var(--ai-chart-background, #fafafa);
      border-top: 1px solid var(--tblr-border-color, #dadcde);
      display: grid;
      gap: 0.75rem;
      padding: 1rem;
   }
   .ai-distribution-widget-item {
      align-items: center;
      background: rgba(255, 255, 255, 0.74);
      border: 1px solid rgba(15, 23, 42, 0.06);
      border-radius: 8px;
      display: grid;
      gap: 0.75rem;
      grid-template-columns: minmax(8rem, 1fr) minmax(8rem, 2fr);
      min-height: 3.4rem;
      padding: 0.65rem 0.8rem;
   }
   .ai-distribution-widget-label {
      color: #1f2937;
      font-weight: 600;
      line-height: 1.25;
      overflow-wrap: anywhere;
   }
   .ai-distribution-widget-bar_vertical {
      align-items: end;
      grid-auto-flow: column;
      grid-auto-columns: minmax(5rem, 1fr);
      overflow-x: auto;
   }
   .ai-distribution-widget-bar_vertical .ai-distribution-widget-item,
   .ai-distribution-widget-pie .ai-distribution-widget-item,
   .ai-distribution-widget-donut .ai-distribution-widget-item,
   .ai-distribution-widget-half_pie .ai-distribution-widget-item,
   .ai-distribution-widget-half_donut .ai-distribution-widget-item,
   .ai-distribution-widget-numbers .ai-distribution-widget-item,
   .ai-distribution-widget-summary_number .ai-distribution-widget-item {
      grid-template-columns: 1fr;
      justify-items: center;
      text-align: center;
   }
   .ai-distribution-widget-summary_number {
      grid-template-columns: repeat(auto-fit, minmax(8rem, 1fr));
   }
   .ai-distribution-widget-numbers .ai-distribution-widget-item,
   .ai-distribution-widget-summary_number .ai-distribution-widget-item {
      background: linear-gradient(135deg, var(--ai-chart-soft, #f8fafc), rgba(255, 255, 255, 0.92));
      border-left: 4px solid var(--ai-chart-color, #2563eb);
   }
   .ai-distribution-pie-summary {
      align-items: center;
      background: var(--ai-chart-background, #fafafa);
      border-top: 1px solid var(--tblr-border-color, #dadcde);
      display: grid;
      gap: 1rem;
      grid-template-columns: minmax(8rem, 14rem) minmax(0, 1fr);
      overflow: hidden;
      padding: 1rem;
   }
   .ai-distribution-pie-visual {
      align-items: center;
      display: flex;
      justify-content: center;
      min-height: 13rem;
   }
   .ai-distribution-pie-shape {
      align-items: center;
      aspect-ratio: 1;
      background: var(--ai-pie-gradient);
      border: 1px solid rgba(15, 23, 42, 0.06);
      border-radius: 50%;
      box-shadow: 0 14px 24px rgba(15, 23, 42, 0.08);
      display: inline-flex;
      height: min(12rem, 100%);
      justify-content: center;
      max-width: 100%;
      position: relative;
      width: min(12rem, 100%);
   }
   .ai-distribution-pie-summary-donut .ai-distribution-pie-shape,
   .ai-distribution-pie-summary-half_donut .ai-distribution-pie-shape {
      background:
         radial-gradient(circle at center, var(--ai-chart-background, #fafafa) 0 48%, transparent 49%),
         var(--ai-pie-gradient);
   }
   .ai-distribution-pie-summary-half_pie .ai-distribution-pie-visual,
   .ai-distribution-pie-summary-half_donut .ai-distribution-pie-visual {
      min-height: 7rem;
      overflow: hidden;
   }
   .ai-distribution-pie-summary-half_pie .ai-distribution-pie-shape,
   .ai-distribution-pie-summary-half_donut .ai-distribution-pie-shape {
      transform: translateY(50%);
   }
   .ai-distribution-pie-total {
      align-items: center;
      background: rgba(255, 255, 255, 0.88);
      border: 1px solid rgba(15, 23, 42, 0.06);
      border-radius: 999px;
      box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
      color: #0f172a;
      display: inline-flex;
      font-size: 1.35rem;
      font-weight: 800;
      justify-content: center;
      min-height: 3.1rem;
      min-width: 3.1rem;
      padding: 0 0.55rem;
   }
   .ai-distribution-pie-legend {
      display: grid;
      gap: 0.55rem;
      min-width: 0;
      overflow: hidden;
   }
   .ai-distribution-pie-legend-item {
      align-items: center;
      background: rgba(255, 255, 255, 0.72);
      border: 1px solid rgba(15, 23, 42, 0.06);
      border-left: 4px solid var(--ai-chart-color, #2563eb);
      border-radius: 8px;
      display: grid;
      gap: 0.5rem;
      grid-template-columns: auto minmax(0, 1fr) auto;
      min-height: 2.5rem;
      min-width: 0;
      padding: 0.45rem 0.65rem;
   }
   .ai-distribution-pie-dot {
      background: var(--ai-chart-color, #2563eb);
      border-radius: 999px;
      display: inline-block;
      height: 0.75rem;
      width: 0.75rem;
   }
   .ai-distribution-pie-label {
      color: #1f2937;
      font-weight: 600;
      min-width: 0;
      overflow-wrap: anywhere;
   }
   .ai-distribution-pie-metric {
      color: var(--ai-muted);
      font-weight: 700;
      overflow-wrap: anywhere;
      text-align: right;
   }
   @container (max-width: 34rem) {
      .ai-distribution-pie-summary {
         grid-template-columns: 1fr;
      }
      .ai-distribution-pie-visual {
         min-height: 10rem;
      }
      .ai-distribution-pie-shape {
         height: 9.5rem;
         width: 9.5rem;
      }
      .ai-distribution-pie-legend-item {
         grid-template-columns: auto minmax(0, 1fr);
      }
      .ai-distribution-pie-metric {
         grid-column: 2;
         text-align: left;
      }
   }
   @media (max-width: 768px) {
      .ai-distribution-pie-summary {
         grid-template-columns: 1fr;
      }
      .ai-distribution-pie-legend-item {
         grid-template-columns: auto minmax(0, 1fr);
      }
      .ai-distribution-pie-metric {
         grid-column: 2;
      }
   }
   .ai-distribution-empty {
      align-items: center;
      color: var(--ai-muted);
      display: flex;
      justify-content: center;
      min-height: 6rem;
      padding: 1rem;
      text-align: center;
   }
   .ai-distribution-chart-settings {
      border-top: 1px solid var(--tblr-border-color, #dadcde);
      margin-top: 1rem;
      padding-top: 1rem;
   }
   .ai-distribution-chart-settings > summary {
      align-items: center;
      cursor: pointer;
      display: inline-flex;
      gap: 0.5rem;
      font-weight: 600;
      list-style: none;
   }
   .ai-distribution-chart-settings > summary::-webkit-details-marker {
      display: none;
   }
   .ai-distribution-chart-settings > summary::after {
      border-bottom: 1.5px solid currentColor;
      border-right: 1.5px solid currentColor;
      content: "";
      display: inline-block;
      height: 0.45rem;
      margin-top: -0.2rem;
      transform: rotate(45deg);
      transition: transform 0.15s ease;
      width: 0.45rem;
   }
   .ai-distribution-chart-settings[open] > summary::after {
      margin-top: 0.2rem;
      transform: rotate(225deg);
   }
</style>

<div class="m-3 ai-distribution-page">
   <h3 class="ai-distribution-title">
      <span class="ai-distribution-title-icon"><i class="ti ti-route"></i></span>
      <span><?php echo __('Distribuições de chamados', 'atribuicaointeligente'); ?></span>
   </h3>

   <form method="get" action="<?php echo plugin_atribuicaointeligente_distribution_escape($formAction); ?>" class="card ai-distribution-filter-card mb-3">
      <?php if ($embedded): ?>
         <?php echo Html::hidden('forcetab', ['value' => 'PluginAtribuicaointeligenteConfig$5']); ?>
      <?php endif; ?>
      <?php echo Html::hidden('distribution_filter', ['value' => '1']); ?>
      <div class="card-body">
         <div class="row g-3">
            <div class="col-12 col-md-3">
               <label class="form-label" for="date_start"><?php echo __('Início', 'atribuicaointeligente'); ?></label>
               <input class="form-control" type="date" id="date_start" name="date_start" value="<?php echo plugin_atribuicaointeligente_distribution_escape($filters['date_start']); ?>">
            </div>
            <div class="col-12 col-md-3">
               <label class="form-label" for="date_end"><?php echo __('Fim', 'atribuicaointeligente'); ?></label>
               <input class="form-control" type="date" id="date_end" name="date_end" value="<?php echo plugin_atribuicaointeligente_distribution_escape($filters['date_end']); ?>">
            </div>
            <div class="col-12 col-md-3">
               <label class="form-label"><?php echo __('Entidade', 'atribuicaointeligente'); ?></label>
               <?php
               plugin_atribuicaointeligente_distribution_entity_dropdown('entities_id', $filters['entities_id']);
               ?>
            </div>
            <div class="col-12 col-md-3">
               <label class="form-label"><?php echo __('Categoria', 'atribuicaointeligente'); ?></label>
               <?php
               Dropdown::show('ITILCategory', [
                  'name'                => 'itilcategories_id',
                  'value'               => (int) $filters['itilcategories_id'],
                  'display_emptychoice' => true,
                  'width'               => '100%',
               ]);
               ?>
            </div>
            <div class="col-12 col-md-3">
               <label class="form-label" for="action_type"><?php echo __('Tipo de distribuição', 'atribuicaointeligente'); ?></label>
               <select class="form-select" id="action_type" name="action_type">
                  <option value=""><?php echo __('Todos'); ?></option>
                  <?php foreach (PluginAtribuicaointeligenteDistributionLog::getAllowedActions() as $action): ?>
                     <option value="<?php echo plugin_atribuicaointeligente_distribution_escape($action); ?>" <?php echo $filters['action_type'] === $action ? 'selected' : ''; ?>>
                        <?php echo plugin_atribuicaointeligente_distribution_escape(PluginAtribuicaointeligenteDistributionLog::getActionLabel($action)); ?>
                     </option>
                  <?php endforeach; ?>
               </select>
            </div>
            <div class="col-12 col-md-3">
               <label class="form-label" for="distribution_source"><?php echo __('Origem', 'atribuicaointeligente'); ?></label>
               <select class="form-select" id="distribution_source" name="distribution_source">
                  <option value=""><?php echo __('Todas'); ?></option>
                  <?php foreach (PluginAtribuicaointeligenteDistributionLog::getAllowedSources() as $source): ?>
                     <option value="<?php echo plugin_atribuicaointeligente_distribution_escape($source); ?>" <?php echo $filters['source'] === $source ? 'selected' : ''; ?>>
                        <?php echo plugin_atribuicaointeligente_distribution_escape(PluginAtribuicaointeligenteDistributionLog::getSourceLabel($source)); ?>
                     </option>
                  <?php endforeach; ?>
               </select>
            </div>
            <div class="col-12 col-md-3">
               <label class="form-label" for="evolution_period"><?php echo __('Período da evolução', 'atribuicaointeligente'); ?></label>
               <select class="form-select" id="evolution_period" name="evolution_period">
                  <?php foreach (plugin_atribuicaointeligente_distribution_periods() as $period => $label): ?>
                     <option value="<?php echo plugin_atribuicaointeligente_distribution_escape($period); ?>" <?php echo $filters['evolution_period'] === $period ? 'selected' : ''; ?>>
                        <?php echo plugin_atribuicaointeligente_distribution_escape($label); ?>
                     </option>
                  <?php endforeach; ?>
               </select>
            </div>
         </div>
         <details class="ai-distribution-chart-settings">
            <summary><?php echo __('Configurações de gráficos', 'atribuicaointeligente'); ?></summary>
            <div class="row g-3 mt-2">
               <?php
               plugin_atribuicaointeligente_distribution_chart_dropdown('chart_categories', $filters, __('Por categoria', 'atribuicaointeligente'));
               plugin_atribuicaointeligente_distribution_chart_dropdown('chart_summary_distributors', $filters, __('Resumo por distribuidor', 'atribuicaointeligente'));
               plugin_atribuicaointeligente_distribution_chart_dropdown('chart_daily', $filters, __('Evolução', 'atribuicaointeligente'));
               plugin_atribuicaointeligente_distribution_chart_dropdown('chart_top_distributors', $filters, __('Top distribuidores', 'atribuicaointeligente'));
               plugin_atribuicaointeligente_distribution_chart_dropdown('chart_actuation', $filters, __('Atuação', 'atribuicaointeligente'));
               plugin_atribuicaointeligente_distribution_chart_dropdown('chart_top_technicians', $filters, __('Top técnicos', 'atribuicaointeligente'));
               plugin_atribuicaointeligente_distribution_chart_dropdown('chart_transfers', $filters, __('Transferências', 'atribuicaointeligente'));
               ?>
               <div class="col-12 col-md-3">
                  <input type="hidden" name="chart_gradient" value="0">
                  <label class="form-check mt-4">
                     <input class="form-check-input" type="checkbox" name="chart_gradient" value="1" <?php echo !empty($filters['chart_gradient']) ? 'checked' : ''; ?>>
                     <span class="form-check-label"><?php echo __('Usar paleta de gradiente', 'atribuicaointeligente'); ?></span>
                  </label>
               </div>
               <div class="col-12 col-md-3">
                  <input type="hidden" name="chart_show_labels" value="0">
                  <label class="form-check mt-4">
                     <input class="form-check-input" type="checkbox" name="chart_show_labels" value="1" <?php echo !empty($filters['chart_show_labels']) ? 'checked' : ''; ?>>
                     <span class="form-check-label"><?php echo __('Exibir rótulos de valor em pontos / barras', 'atribuicaointeligente'); ?></span>
                  </label>
               </div>
               <div class="col-12 col-md-3">
                  <label class="form-label" for="chart_data_limit"><?php echo __('Limitar número de dados', 'atribuicaointeligente'); ?></label>
                  <input class="form-control" type="number" min="1" max="50" id="chart_data_limit" name="chart_data_limit" value="<?php echo (int) $filters['chart_data_limit']; ?>">
               </div>
               <div class="col-12 col-md-3">
                  <label class="form-label" for="chart_background"><?php echo __('Cor do plano de fundo', 'atribuicaointeligente'); ?></label>
                  <input class="form-control" type="text" id="chart_background" name="chart_background" value="<?php echo plugin_atribuicaointeligente_distribution_escape($filters['chart_background']); ?>" placeholder="#fafafa">
               </div>
               <?php
               plugin_atribuicaointeligente_distribution_chart_color_input('chart_color_categories', $filters, __('Cor por categoria', 'atribuicaointeligente'));
               plugin_atribuicaointeligente_distribution_chart_color_input('chart_color_summary_distributors', $filters, __('Cor resumo distribuidor', 'atribuicaointeligente'));
               plugin_atribuicaointeligente_distribution_chart_color_input('chart_color_daily', $filters, __('Cor evolução', 'atribuicaointeligente'));
               plugin_atribuicaointeligente_distribution_chart_color_input('chart_color_top_distributors', $filters, __('Cor top distribuidores', 'atribuicaointeligente'));
               plugin_atribuicaointeligente_distribution_chart_color_input('chart_color_actuation', $filters, __('Cor atuação', 'atribuicaointeligente'));
               plugin_atribuicaointeligente_distribution_chart_color_input('chart_color_top_technicians', $filters, __('Cor top técnicos', 'atribuicaointeligente'));
               plugin_atribuicaointeligente_distribution_chart_color_input('chart_color_transfers', $filters, __('Cor transferências', 'atribuicaointeligente'));
               ?>
            </div>
         </details>
      </div>
      <div class="card-footer d-flex gap-2">
         <button class="btn btn-primary" type="submit">
            <i class="ti ti-filter me-1"></i>
            <?php echo __('Filtrar', 'atribuicaointeligente'); ?>
         </button>
         <a class="btn btn-outline-secondary" href="<?php echo plugin_atribuicaointeligente_distribution_escape(plugin_atribuicaointeligente_distribution_filter_url($embedded, ['distribution_clear' => 1])); ?>">
            <i class="ti ti-x me-1"></i>
            <?php echo __('Limpar', 'atribuicaointeligente'); ?>
         </a>
      </div>
   </form>

   <div class="row g-3 mb-3">
      <div class="col-12 col-sm-6 col-xl">
         <div class="card ai-distribution-kpi" style="--ai-kpi-color: #2563eb; --ai-kpi-soft: #dbeafe;">
            <div class="card-body">
               <div class="ai-distribution-kpi-label"><?php echo __('Chamados distintos', 'atribuicaointeligente'); ?></div>
               <div class="ai-distribution-kpi-value"><?php echo (int) $distinctTickets; ?></div>
               <div class="ai-distribution-kpi-caption"><?php echo __('Base do período filtrado', 'atribuicaointeligente'); ?></div>
            </div>
         </div>
      </div>
      <div class="col-12 col-sm-6 col-xl">
         <div class="card ai-distribution-kpi" style="--ai-kpi-color: #16a34a; --ai-kpi-soft: #dcfce7;">
            <div class="card-body">
               <div class="ai-distribution-kpi-label"><?php echo __('Automação total', 'atribuicaointeligente'); ?></div>
               <div class="ai-distribution-kpi-value"><?php echo $automationRate; ?>%</div>
               <div class="ai-distribution-kpi-caption"><?php echo $automationTickets . ' ' . __('chamados com automação', 'atribuicaointeligente'); ?></div>
            </div>
         </div>
      </div>
      <div class="col-12 col-sm-6 col-xl">
         <div class="card ai-distribution-kpi" style="--ai-kpi-color: #059669; --ai-kpi-soft: #d1fae5;">
            <div class="card-body">
               <div class="ai-distribution-kpi-label"><?php echo __('Automação integral', 'atribuicaointeligente'); ?></div>
               <div class="ai-distribution-kpi-value"><?php echo $automationIntegralTickets; ?></div>
               <div class="ai-distribution-kpi-caption"><?php echo __('Sem intervenção humana', 'atribuicaointeligente'); ?></div>
            </div>
         </div>
      </div>
      <div class="col-12 col-sm-6 col-xl">
         <div class="card ai-distribution-kpi" style="--ai-kpi-color: #f59e0b; --ai-kpi-soft: #fef3c7;">
            <div class="card-body">
               <div class="ai-distribution-kpi-label"><?php echo __('Automação parcial', 'atribuicaointeligente'); ?></div>
               <div class="ai-distribution-kpi-value"><?php echo $automationPartialTickets; ?></div>
               <div class="ai-distribution-kpi-caption"><?php echo __('Categoria ou ajuste humano', 'atribuicaointeligente'); ?></div>
            </div>
         </div>
      </div>
      <div class="col-12 col-sm-6 col-xl">
         <div class="card ai-distribution-kpi" style="--ai-kpi-color: #dc2626; --ai-kpi-soft: #fee2e2;">
            <div class="card-body">
               <div class="ai-distribution-kpi-label"><?php echo __('Atuação manual', 'atribuicaointeligente'); ?></div>
               <div class="ai-distribution-kpi-value"><?php echo $manualTickets; ?></div>
               <div class="ai-distribution-kpi-caption"><?php echo __('Técnico definido manualmente', 'atribuicaointeligente'); ?></div>
            </div>
         </div>
      </div>
      <div class="col-12 col-sm-6 col-xl">
         <div class="card ai-distribution-kpi" style="--ai-kpi-color: #7c3aed; --ai-kpi-soft: #ede9fe;">
            <div class="card-body">
               <div class="ai-distribution-kpi-label"><?php echo __('Transferências', 'atribuicaointeligente'); ?></div>
               <div class="ai-distribution-kpi-value"><?php echo (int) $transferTickets; ?></div>
               <div class="ai-distribution-kpi-caption"><?php echo $transferRate; ?>% <?php echo __('dos chamados', 'atribuicaointeligente'); ?></div>
            </div>
         </div>
      </div>
   </div>

   <div class="card ai-distribution-card mb-3">
      <div class="card-header">
         <h4 class="card-title mb-0"><?php echo __('Resumo por distribuidor', 'atribuicaointeligente'); ?></h4>
      </div>
      <?php if ($filters['chart_summary_distributors'] === 'table'): ?>
         <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
               <thead>
                  <tr>
                     <th><?php echo __('Distribuidor', 'atribuicaointeligente'); ?></th>
                     <th><?php echo __('Chamados', 'atribuicaointeligente'); ?></th>
                     <th><?php echo __('Transferências', 'atribuicaointeligente'); ?></th>
                  </tr>
               </thead>
               <tbody>
                  <?php if (empty($summaryRows)): ?>
                     <tr>
                        <td colspan="3" class="text-muted text-center"><?php echo __('Nenhum registro encontrado.', 'atribuicaointeligente'); ?></td>
                     </tr>
                  <?php endif; ?>
                  <?php foreach ($summaryRows as $row): ?>
                     <tr>
                        <td><?php echo (int) $row['users_id_actor'] > 0 ? plugin_atribuicaointeligente_distribution_escape(getUserName((int) $row['users_id_actor'])) : __('Sistema', 'atribuicaointeligente'); ?></td>
                        <td><?php echo (int) $row['tickets_count']; ?></td>
                        <td><?php echo (int) $row['transfer_tickets']; ?></td>
                     </tr>
                  <?php endforeach; ?>
               </tbody>
            </table>
         </div>
      <?php else: ?>
         <?php
         plugin_atribuicaointeligente_distribution_render_widget(
            $summaryRows,
            'users_id_actor',
            'tickets_count',
            $filters['chart_summary_distributors'],
            $filters,
            static function (array $row): string {
               $name = (int) ($row['users_id_actor'] ?? 0) > 0 ? getUserName((int) $row['users_id_actor']) : __('Sistema', 'atribuicaointeligente');
               $transfers = (int) ($row['transfer_tickets'] ?? 0);
               return $name . ' (' . $transfers . ' ' . __('transferências', 'atribuicaointeligente') . ')';
            },
            '3',
            'chart_color_summary_distributors'
         );
         ?>
      <?php endif; ?>
   </div>

   <div class="row g-3 mb-3">
      <div class="col-12 col-xl-6">
         <div class="card ai-distribution-card h-100">
            <div class="card-header">
               <h4 class="card-title mb-0"><?php echo __('Distribuição por categoria', 'atribuicaointeligente'); ?></h4>
            </div>
            <?php if ($filters['chart_categories'] === 'table'): ?>
               <div class="table-responsive">
                  <table class="table table-hover mb-0">
                     <thead>
                        <tr>
                           <th><?php echo __('Categoria', 'atribuicaointeligente'); ?></th>
                           <th class="text-end"><?php echo __('Chamados', 'atribuicaointeligente'); ?></th>
                        </tr>
                     </thead>
                     <tbody>
                        <?php if (empty($categoryRows)): ?>
                           <tr>
                              <td colspan="2" class="text-muted text-center"><?php echo __('Nenhum registro encontrado.', 'atribuicaointeligente'); ?></td>
                           </tr>
                        <?php endif; ?>
                        <?php foreach ($categoryRows as $row): ?>
                           <tr>
                              <td><?php echo plugin_atribuicaointeligente_distribution_escape(plugin_atribuicaointeligente_distribution_category_label($row['itilcategories_id'] ?? 0)); ?></td>
                              <td class="text-end"><?php echo (int) ($row['tickets_count'] ?? 0); ?></td>
                           </tr>
                        <?php endforeach; ?>
                     </tbody>
                  </table>
               </div>
            <?php else: ?>
               <?php
               plugin_atribuicaointeligente_distribution_render_widget(
                  $categoryRows,
                  'itilcategories_id',
                  'tickets_count',
                  $filters['chart_categories'],
                  $filters,
                  static function (array $row): string {
                     return plugin_atribuicaointeligente_distribution_category_label($row['itilcategories_id'] ?? 0);
                  },
                  '3',
                  'chart_color_categories'
               );
               ?>
            <?php endif; ?>
         </div>
      </div>

      <div class="col-12 col-xl-6">
         <div class="card ai-distribution-card h-100">
            <div class="card-header">
               <h4 class="card-title mb-0"><?php echo __('Evolução no período', 'atribuicaointeligente'); ?></h4>
            </div>
            <?php if ($filters['chart_daily'] === 'table'): ?>
               <div class="table-responsive">
                  <table class="table table-hover mb-0">
                     <thead>
                        <tr>
                           <th><?php echo plugin_atribuicaointeligente_distribution_escape(plugin_atribuicaointeligente_distribution_periods()[$filters['evolution_period']] ?? __('Período', 'atribuicaointeligente')); ?></th>
                           <th class="text-end"><?php echo __('Chamados', 'atribuicaointeligente'); ?></th>
                        </tr>
                     </thead>
                     <tbody>
                        <?php if (empty($dailyRows)): ?>
                           <tr>
                              <td colspan="2" class="text-muted text-center"><?php echo __('Nenhum registro encontrado.', 'atribuicaointeligente'); ?></td>
                           </tr>
                        <?php endif; ?>
                        <?php foreach ($dailyRows as $row): ?>
                           <tr>
                              <td><?php echo plugin_atribuicaointeligente_distribution_escape($row['distribution_period'] ?? ''); ?></td>
                              <td class="text-end"><?php echo (int) ($row['tickets_count'] ?? 0); ?></td>
                           </tr>
                        <?php endforeach; ?>
                     </tbody>
                  </table>
               </div>
            <?php else: ?>
               <?php
               plugin_atribuicaointeligente_distribution_render_widget(
                  $dailyRows,
                  'distribution_period',
                  'tickets_count',
                  $filters['chart_daily'],
                  $filters,
                  null,
                  '3',
                  'chart_color_daily'
               );
               ?>
            <?php endif; ?>
         </div>
      </div>
   </div>

   <div class="row g-3 mb-3">
      <div class="col-12 col-xl-4">
         <div class="card ai-distribution-card h-100">
            <div class="card-header">
               <h4 class="card-title mb-0"><?php echo __('Top distribuidores', 'atribuicaointeligente'); ?></h4>
            </div>
            <?php if ($filters['chart_top_distributors'] === 'table'): ?>
               <div class="table-responsive">
                  <table class="table table-striped mb-0">
                     <thead>
                        <tr>
                           <th><?php echo __('Distribuidor', 'atribuicaointeligente'); ?></th>
                           <th class="text-end"><?php echo __('Chamados', 'atribuicaointeligente'); ?></th>
                        </tr>
                     </thead>
                     <tbody>
                        <?php if (empty($topDistributorRows)): ?>
                           <tr>
                              <td colspan="2" class="text-muted text-center"><?php echo __('Nenhum registro encontrado.', 'atribuicaointeligente'); ?></td>
                           </tr>
                        <?php endif; ?>
                        <?php foreach ($topDistributorRows as $row): ?>
                           <tr>
                              <td><?php echo (int) $row['users_id_actor'] > 0 ? plugin_atribuicaointeligente_distribution_escape(getUserName((int) $row['users_id_actor'])) : __('Sistema', 'atribuicaointeligente'); ?></td>
                              <td class="text-end"><?php echo (int) ($row['tickets_count'] ?? 0); ?></td>
                           </tr>
                        <?php endforeach; ?>
                     </tbody>
                  </table>
               </div>
            <?php else: ?>
               <?php
               plugin_atribuicaointeligente_distribution_render_widget(
                  $topDistributorRows,
                  'users_id_actor',
                  'tickets_count',
                  $filters['chart_top_distributors'],
                  $filters,
                  static function (array $row): string {
                     return (int) ($row['users_id_actor'] ?? 0) > 0 ? getUserName((int) $row['users_id_actor']) : __('Sistema', 'atribuicaointeligente');
                  },
                  '3',
                  'chart_color_top_distributors'
               );
               ?>
            <?php endif; ?>
         </div>
      </div>

      <div class="col-12 col-xl-4">
         <div class="card ai-distribution-card h-100">
            <div class="card-header">
               <h4 class="card-title mb-0"><?php echo __('Atuação por chamado', 'atribuicaointeligente'); ?></h4>
            </div>
            <?php if ($filters['chart_actuation'] === 'table'): ?>
               <div class="table-responsive">
                  <table class="table table-hover mb-0">
                     <thead>
                        <tr>
                           <th><?php echo __('Classificação', 'atribuicaointeligente'); ?></th>
                           <th class="text-end"><?php echo __('Chamados', 'atribuicaointeligente'); ?></th>
                        </tr>
                     </thead>
                     <tbody>
                        <?php foreach ($actuationRows as $row): ?>
                           <tr>
                              <td><?php echo plugin_atribuicaointeligente_distribution_escape($row['label'] ?? ''); ?></td>
                              <td class="text-end"><?php echo (int) ($row['tickets_count'] ?? 0); ?></td>
                           </tr>
                        <?php endforeach; ?>
                     </tbody>
                  </table>
               </div>
            <?php else: ?>
               <?php
               plugin_atribuicaointeligente_distribution_render_widget(
                  $actuationRows,
                  'label',
                  'tickets_count',
                  $filters['chart_actuation'],
                  $filters,
                  null,
                  '3',
                  'chart_color_actuation'
               );
               ?>
            <?php endif; ?>
         </div>
      </div>

      <div class="col-12 col-xl-4">
         <div class="card ai-distribution-card h-100">
            <div class="card-header">
               <h4 class="card-title mb-0"><?php echo __('Top técnicos destino', 'atribuicaointeligente'); ?></h4>
            </div>
            <?php if ($filters['chart_top_technicians'] === 'table'): ?>
               <div class="table-responsive">
                  <table class="table table-striped mb-0">
                     <thead>
                        <tr>
                           <th><?php echo __('Técnico', 'atribuicaointeligente'); ?></th>
                           <th class="text-end"><?php echo __('Chamados', 'atribuicaointeligente'); ?></th>
                        </tr>
                     </thead>
                     <tbody>
                        <?php if (empty($topTechnicianRows)): ?>
                           <tr>
                              <td colspan="2" class="text-muted text-center"><?php echo __('Nenhum registro encontrado.', 'atribuicaointeligente'); ?></td>
                           </tr>
                        <?php endif; ?>
                        <?php foreach ($topTechnicianRows as $row): ?>
                           <tr>
                              <td><?php echo plugin_atribuicaointeligente_distribution_escape(getUserName((int) $row['users_id_to'])); ?></td>
                              <td class="text-end"><?php echo (int) ($row['tickets_count'] ?? 0); ?></td>
                           </tr>
                        <?php endforeach; ?>
                     </tbody>
                  </table>
               </div>
            <?php else: ?>
               <?php
               plugin_atribuicaointeligente_distribution_render_widget(
                  $topTechnicianRows,
                  'users_id_to',
                  'tickets_count',
                  $filters['chart_top_technicians'],
                  $filters,
                  static function (array $row): string {
                     return getUserName((int) ($row['users_id_to'] ?? 0));
                  },
                  '3',
                  'chart_color_top_technicians'
               );
               ?>
            <?php endif; ?>
         </div>
      </div>
   </div>

   <div class="card ai-distribution-card mb-3">
      <div class="card-header">
         <h4 class="card-title mb-0"><?php echo __('Transferências por entidade', 'atribuicaointeligente'); ?></h4>
      </div>
      <?php if ($filters['chart_transfers'] === 'table'): ?>
         <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
               <thead>
                  <tr>
                     <th><?php echo __('Origem', 'atribuicaointeligente'); ?></th>
                     <th><?php echo __('Destino', 'atribuicaointeligente'); ?></th>
                     <th class="text-end"><?php echo __('Chamados', 'atribuicaointeligente'); ?></th>
                  </tr>
               </thead>
               <tbody>
                  <?php if (empty($transferRows)): ?>
                     <tr>
                        <td colspan="3" class="text-muted text-center"><?php echo __('Nenhum registro encontrado.', 'atribuicaointeligente'); ?></td>
                     </tr>
                  <?php endif; ?>
                  <?php foreach ($transferRows as $row): ?>
                     <tr>
                        <td><?php echo plugin_atribuicaointeligente_distribution_escape(plugin_atribuicaointeligente_distribution_entity_label($row['entities_id_from'] ?? 0)); ?></td>
                        <td><?php echo plugin_atribuicaointeligente_distribution_escape(plugin_atribuicaointeligente_distribution_entity_label($row['entities_id_to'] ?? 0)); ?></td>
                        <td class="text-end"><?php echo (int) ($row['tickets_count'] ?? 0); ?></td>
                     </tr>
                  <?php endforeach; ?>
               </tbody>
            </table>
         </div>
      <?php else: ?>
         <?php
         plugin_atribuicaointeligente_distribution_render_widget(
            $transferRows,
            'entities_id_from',
            'tickets_count',
            $filters['chart_transfers'],
            $filters,
            static function (array $row): string {
               return plugin_atribuicaointeligente_distribution_entity_label($row['entities_id_from'] ?? 0)
                  . ' -> '
                  . plugin_atribuicaointeligente_distribution_entity_label($row['entities_id_to'] ?? 0);
            },
            '3',
            'chart_color_transfers'
         );
         ?>
      <?php endif; ?>
   </div>
</div>

<?php
if (!$embedded) {
   Html::footer();
}
