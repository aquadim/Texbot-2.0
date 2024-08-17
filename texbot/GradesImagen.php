<?php
// Генератор изображений (таблиц данных)

namespace Texbot;

class GradesImagen extends GenericImagen {
    
    #region Переменные темы
    protected static array $t_colors = [
        "background"=> [35, 35, 35],
        "title_color"=> [255, 255, 255],
        
        "body_bg_even"=> [43, 43, 43],
        "body_bg_odd" => [48, 48, 48],
        
        "body_lock_even" => [73, 73, 53],
        "body_lock_odd" => [78, 78, 58],
        
        "body_problem_even" => [220, 30, 30],
        "body_problem_odd" => [220, 30, 75],
        
        "body_perfect_even" => [22, 158, 67],
        "body_perfect_odd" => [22, 158, 22],
        
        "body_fg" => [221, 221, 221],
        "body_frame" => [80, 80, 80]
    ];
    
    protected static int $t_body_line_height = 18;
    protected static int $t_title_line_height = 32;
    
    protected static int $t_body_line_spacing = 16;
    protected static int $t_title_line_spacing = 16;
    
    protected static float $t_body_fontsize = 18.0;
    protected static float $t_title_fontsize = 32.0;
    
    protected static string $t_font_filename = root_dir.'/resources/OpenSans-Regular.ttf';
    
    protected static int $t_padding = 16;
    #endregion

	// Отрисовывает задний фон строки таблицы
	// Но:
	// 1. Если по предмету есть двойка, делает градиент справа красным цветов
	// 2. Если по предмету только пятёрки, делает градиент справа фиолетовым
	// 3. Если по предмету выставлена семестровая оценка, градиент слева - жёлтый
	protected static function drawBodyLine
    (
        $im,
        $colors,
        $row_data,
        $is_even,
        $x1,
        $y1,
        $x2,
        $y2
    ) : void {

		// Вычисление левого и правого цвета строки
        /* ВЫГЛЯДИТ ПЛОХО
        if (is_numeric($row_data[2][0])) {
            // Если первым символом в колонке среднего балла является
            // число, значит семестровую оценку выставили
            $right_color_key = 'body_lock';
        } else {
            $right_color_key = 'body_bg';
        }
        */
        $right_color_key = 'body_bg';

		if (str_contains($row_data[1], '2')) {
            // Есть двойка
			$left_color_key = 'body_problem';
		} else {
			// Подсчёт количества троек, четвёрок, пятёрок
			// Т.к. count_chars возвращает массив, в котором индекс - 
            // это номер символа из таблицы ASCII, то символ тройки 
            // соответствует индексу 51, 4 - это 52, 5 - это 53.
			// Двойки не подсчитываем, т.к. в этом блоке кода их не 
            // может быть
			$info = count_chars($row_data[1], 0);
			$count3 = $info[51];
			$count4 = $info[52];
			$count5 = $info[53];
			if ($count3 == 0 && $count4 == 0 && $count5 > 0) {
                // Идеальные оценки
				$left_color_key = 'body_perfect';
			} else {
                // Оценки обычные
				$left_color_key = 'body_bg';
			}
		}
        
        $right_color = static::$t_colors[
            $right_color_key.($is_even ? '_even' : '_odd')
        ];
        $left_color = static::$t_colors[
            $left_color_key.($is_even ? '_even' : '_odd')
        ];

		$steps = 100;
		$block_width = ($x2 - $x1) / $steps;

		// Вычисляем на какое значение нужно увеличивать компоненты
        // цвета в каждом блоке градиента
		$delta_r = ($right_color[0] - $left_color[0]) / $steps;
		$delta_g = ($right_color[1] - $left_color[1]) / $steps;
		$delta_b = ($right_color[2] - $left_color[2]) / $steps;

		$current_r = $left_color[0];
		$current_g = $left_color[1];
		$current_b = $left_color[2];

		// Отрисовка градиента
		for ($i = 0; $i < $steps; $i++) {
			$color = imagecolorallocate(
                $im,
                (int)$current_r,
                (int)$current_g,
                (int)$current_b
            );
			imagefilledrectangle(
                $im,
                $x1 + (int)(ceil($i * $block_width)),
                $y1,
                $x1 + (int)(ceil(($i + 1) * $block_width)),
                $y2,
                $color
            );
			$current_r += $delta_r;
			$current_g += $delta_g;
			$current_b += $delta_b;
		}
	}
}
