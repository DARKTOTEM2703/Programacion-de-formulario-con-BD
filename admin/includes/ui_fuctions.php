<?php
// filepath: c:\xampp\htdocs\Programacion-de-formulario-con-BD\admin\includes\ui_functions.php

/**
 * Funciones para elementos de interfaz de usuario
 */

/**
 * Genera un componente de alerta
 * @param string $message Mensaje
 * @param string $type Tipo de alerta (success, danger, warning, info)
 * @param bool $dismissible Si es cerrable
 * @return string HTML de la alerta
 */
function generateAlert($message, $type = 'info', $dismissible = true)
{
    $icon = '';
    switch ($type) {
        case 'success':
            $icon = '<i class="bi bi-check-circle-fill me-2"></i>';
            break;
        case 'danger':
            $icon = '<i class="bi bi-exclamation-triangle-fill me-2"></i>';
            break;
        case 'warning':
            $icon = '<i class="bi bi-exclamation-circle-fill me-2"></i>';
            break;
        default:
            $icon = '<i class="bi bi-info-circle-fill me-2"></i>';
    }

    $dismiss_button = $dismissible ? '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' : '';

    return '<div class="alert alert-' . $type . ($dismissible ? ' alert-dismissible' : '') . ' fade show" role="alert">
                <div class="d-flex align-items-center">
                    ' . $icon . safeOutput($message) . '
                </div>
                ' . $dismiss_button . '
            </div>';
}

/**
 * Genera un componente de paginación
 * @param int $current_page Página actual
 * @param int $total_pages Total de páginas
 * @param string $base_url URL base
 * @param array $params Parámetros adicionales para la URL
 * @return string HTML de la paginación
 */
function generatePagination($current_page, $total_pages, $base_url, $params = [])
{
    $pagination = '<nav aria-label="Navegación"><ul class="pagination justify-content-center">';

    // Botón anterior
    $prev_disabled = $current_page <= 1 ? 'disabled' : '';
    $prev_url = $base_url . '?page=' . ($current_page - 1);

    // Añadir parámetros adicionales
    foreach ($params as $key => $value) {
        if (!empty($value)) {
            $prev_url .= "&{$key}=" . urlencode($value);
        }
    }

    $pagination .= '<li class="page-item ' . $prev_disabled . '">
                    <a class="page-link" href="' . $prev_url . '">Anterior</a>
                </li>';

    // Números de página
    for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++) {
        $active = $i === $current_page ? 'active' : '';
        $page_url = $base_url . '?page=' . $i;

        foreach ($params as $key => $value) {
            if (!empty($value)) {
                $page_url .= "&{$key}=" . urlencode($value);
            }
        }

        $pagination .= '<li class="page-item ' . $active . '">
                        <a class="page-link" href="' . $page_url . '">' . $i . '</a>
                    </li>';
    }

    // Botón siguiente
    $next_disabled = $current_page >= $total_pages ? 'disabled' : '';
    $next_url = $base_url . '?page=' . ($current_page + 1);

    foreach ($params as $key => $value) {
        if (!empty($value)) {
            $next_url .= "&{$key}=" . urlencode($value);
        }
    }

    $pagination .= '<li class="page-item ' . $next_disabled . '">
                    <a class="page-link" href="' . $next_url . '">Siguiente</a>
                </li>';

    $pagination .= '</ul></nav>';

    return $pagination;
}

/**
 * Genera una tabla de datos
 * @param array $columns Columnas de la tabla
 * @param array $data Datos de la tabla
 * @param array $options Opciones adicionales
 * @return string HTML de la tabla
 */
function generateDataTable($columns, $data, $options = [])
{
    $table = '<div class="table-responsive">';
    $table .= '<table class="table ' . ($options['table_class'] ?? 'table-striped table-hover') . '">';

    // Encabezado
    $table .= '<thead>';
    $table .= '<tr>';
    foreach ($columns as $key => $label) {
        $table .= '<th>' . safeOutput($label) . '</th>';
    }
    if (!empty($options['actions'])) {
        $table .= '<th>' . ($options['actions_label'] ?? 'Acciones') . '</th>';
    }
    $table .= '</tr>';
    $table .= '</thead>';

    // Cuerpo
    $table .= '<tbody>';
    if (!empty($data)) {
        foreach ($data as $row) {
            $table .= '<tr>';
            foreach ($columns as $key => $label) {
                $table .= '<td>' . (isset($row[$key]) ? safeOutput($row[$key]) : '') . '</td>';
            }
            if (!empty($options['actions'])) {
                $table .= '<td>' . $options['actions']($row) . '</td>';
            }
            $table .= '</tr>';
        }
    } else {
        $table .= '<tr><td colspan="' . (count($columns) + (!empty($options['actions']) ? 1 : 0)) . '" class="text-center">No hay datos para mostrar</td></tr>';
    }
    $table .= '</tbody>';

    $table .= '</table>';
    $table .= '</div>';

    return $table;
}

/**
 * Genera un formulario de filtros
 * @param array $fields Campos del formulario
 * @param array $current_values Valores actuales
 * @param string $action URL de acción
 * @return string HTML del formulario
 */
function generateFilterForm($fields, $current_values = [], $action = '')
{
    $form = '<div class="card mb-4">
                <div class="card-body">
                    <form method="get" action="' . $action . '" class="row g-3">';

    foreach ($fields as $field) {
        $current = $current_values[$field['name']] ?? '';
        $col_class = $field['col_class'] ?? 'col-md-3';

        $form .= '<div class="' . $col_class . '">
                    <label for="' . $field['name'] . '" class="form-label">' . $field['label'] . '</label>';

        switch ($field['type']) {
            case 'select':
                $form .= '<select class="form-select" id="' . $field['name'] . '" name="' . $field['name'] . '">';
                foreach ($field['options'] as $value => $label) {
                    $selected = $value == $current ? 'selected' : '';
                    $form .= '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
                }
                $form .= '</select>';
                break;

            default: // text
                $form .= '<input type="text" class="form-control" id="' . $field['name'] . '" name="' . $field['name'] . '" 
                         value="' . safeOutput($current) . '" placeholder="' . ($field['placeholder'] ?? '') . '">';
        }

        $form .= '</div>';
    }

    $form .= '<div class="col-md-auto d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-filter"></i> Filtrar
                </button>
            </div>';

    if (!empty($current_values)) {
        $form .= '<div class="col-md-auto d-flex align-items-end">
                    <a href="' . $action . '" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Limpiar
                    </a>
                </div>';
    }

    $form .= '</form>
            </div>
        </div>';

    return $form;
}
