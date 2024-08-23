<?php
// Генератор изображений (таблиц данных)

namespace Texbot;

class GenericImagen {
    
    #region Переменные темы
    protected static array $t_colors = [
        "background"=> [220,220,220],
        "title_color"=> [30, 30, 30],
        "body_bg_even"=> [180, 180, 170],
        "body_bg_odd" => [210, 210, 200],
        "body_fg" => [40, 40, 40],
        "body_frame" => [25, 25, 25]
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
    
    // Принимает данные, возвращает путь к сохранённому изображению
    // $data - данные в табличной форме
    // $legend - названия колонок
    // $title - подпись таблицы
    // $body_line_constraints - максимум символов в колонке таблицы
    // $title_line_constraint - максимум символов в названии таблицы
    public static function generateTable
    (
        array $data,
        array $legend,
        string $title,
        array $body_line_constraints,
        int $title_line_constraint
    ) : string
    {
        array_unshift($data, $legend);
        
        $width = count($data[0]);
		$height = count($data);

        // Для хранения высоты строк таблицы в пикселях (яйчейки могут 
        // иметь несколько строк)
        $row_sizes = [];

        // Для хранения ширины столбцов таблицы в пикселях (для полного 
        // вмещения текста)
        $col_sizes = array_fill(0, $width, 0);

        // Для хранения яйчеек текста
        $cells = [];

        #region Определение размеров тела таблицы
        // заодно разбиваем длинный текст на строки
        for ($y = 0; $y < $height; $y++) {
            // -- Обработка одной строки таблицы --

            $row_height = 0; // Высота отрисованной строки в пикселях
            $row_lines = []; // Массив, содержащий строки яйчеек
            
            for ($x = 0; $x < $width; $x++) {
                // 1. Узнаём какие строки текста должны быть в яйчейке
                if ($data[$y][$x] == null) {
					$cell_lines = [' '];
				} else {
					$cell_lines = self::splitLongString(
                        $data[$y][$x],
                        $body_line_constraints[$x]
                    );
				}
                $row_lines[] = $cell_lines;

                // 2. Вычисление и сохранение размеров текста в яйчейке
                list($cell_width, $cell_height) = self::getTextSize(
                    $cell_lines,
                    static::$t_body_line_height,
                    static::$t_body_line_spacing,
                    static::$t_font_filename,
                    static::$t_body_fontsize
                );

                $cell_width += static::$t_padding * 2;
                $cell_height += static::$t_padding * 2;

                // 3. Определение макс. размера ширины колонки и высоты
                // текущей строки
                $col_sizes[$x] = max($col_sizes[$x], $cell_width);
                $row_height = max($row_height, $cell_height);
            }
            $row_sizes[] = $row_height;
            $cells[] = $row_lines;
        }
		$body_width = array_sum($col_sizes);
		$body_height = array_sum($row_sizes);

        // Обработка названия таблицы
        $title_lines = self::splitLongString(
            $title,
            $title_line_constraint
        );
        list($title_width, $title_height) = self::getTextSize(
            $title_lines,
            static::$t_title_line_height,
            static::$t_title_line_spacing,
            static::$t_font_filename,
            static::$t_title_fontsize
        );

        // Вычисление размеров таблицы. Пытаемся сделать квадрат
        $table_width = max($body_width, $title_width);
        // Заголовок+пробел+тело
		$table_height = $body_height + $title_height + static::$t_padding;

        if ($table_width > $table_height) {
            $table_height = $table_width;
        } else {
            $table_width = $table_height;
        }
        #endregion

        #region Отрисовка
        
        // Нужно добавлять один пиксель т.к. недостаёт до padding
        // У ширины +1 закомментировано потому что вот вам, получайте 
        // перфекционисты!!
        $im = imagecreatetruecolor(
            $table_width + static::$t_padding * 2 /* + 1 */,
            $table_height + static::$t_padding * 2 + 1
        );

        $gdcolors = [];
		foreach (static::$t_colors as $color_name => $color) {
			$gdcolors[$color_name] = imagecolorallocate(
                $im,
                $color[0],
                $color[1],
                $color[2]
            );
		}

        // Заполнение заднего фона
		imagefilledrectangle(
            $im,
            0,
            0,
            $table_width + static::$t_padding * 2,
            $table_height + static::$t_padding * 2,
            $gdcolors['background']
        );

        // Отрисовка названия
        // Пробел + высота одной строки
		$line_y = static::$t_padding + static::$t_title_line_height;
		foreach ($title_lines as $line) {
            imagettftext(
                $im,
                static::$t_title_fontsize,
                0,
                static::$t_padding,
                $line_y,
                $gdcolors['title_color'],
                static::$t_font_filename,
                $line
            );
            
            // Добавление ещё строки
			$line_y += static::$t_title_line_height + static::$t_title_line_spacing;
		}

        // Отрисовка тела таблицы
        $line_y = $line_y + static::$t_padding - static::$t_title_line_height - static::$t_title_line_spacing;
        $body_y = $line_y;
        
        for ($y = 0; $y < $height; $y++) {
            // Задний фон строки таблицы
            static::drawBodyLine(
                $im,
                $gdcolors,
                $data[$y],
                ($y % 2 == 0),

                static::$t_padding, // x1
                $line_y, // y1

                static::$t_padding + $body_width, // x2
                $line_y + $row_sizes[$y] // y2
			);

            // Содержимое яйчеек
            $celltext_x = static::$t_padding * 2;
            for ($x = 0; $x < $width; $x++) {
                $celltext_y = $line_y + static::$t_padding;

                foreach ($cells[$y][$x] as $cell_line) {
                    imagettftext(
                        $im,
                        static::$t_body_fontsize,
                        0,
                        $celltext_x,
                        $celltext_y + static::$t_body_line_height,
                        $gdcolors['body_fg'],
                        static::$t_font_filename,
                        $cell_line
                    );
                    $celltext_y +=
                        static::$t_body_line_height +
                        static::$t_body_line_spacing;
                }
                $celltext_x += $col_sizes[$x];
            }
            $line_y += $row_sizes[$y];
        }

        // Рамка таблицы
        imagerectangle(
            $im,

            static::$t_padding,
            $body_y,

            static::$t_padding + $body_width,
            $body_y + $body_height,

            $gdcolors['body_frame']
        );

        // Черта между легендой и содержимым
        $sep_y = $body_y + $row_sizes[0];
        imagedashedline(
            $im,
            static::$t_padding,
            $sep_y,
            static::$t_padding + $body_width,
            $sep_y,
            $gdcolors['body_frame']
        );
        
        #endregion

        #region Сохранение изображения
        $filename = rand(11111,99999).".png";
        $filename_abs = "/tmp/$filename";
		imagepng($im, $filename_abs);
		return $filename_abs;
        #endregion
    }
    
    // Разбивает длинную строку на линии, перенося слова
    // (слова - это участки текста, разделённые пробелами)
    // $text - текст, который нужно разделить
    // $line_size - максимальная длина текста в колонке
	private static function splitLongString
    (
        string $text,
        int $line_size
    ) : array 
    {

		// Не разделять слова
		if ($line_size == 0) {
			return [$text];
		}
		
		$output = [];
		$current_line = "";
		$words = explode(" ", $text);

		for ($i = 0; $i < count($words); $i++) {

            // Если текущая строка после прибавления будет больше чем line_size,
            // то её нужно будет перенести на новую строку.
            // Если после переноса строка не вмещается в line_size, то разбить
            // строку вручную на участки по line_size символов.
            // А если строка вмещается, просто прибавить её.

            $current_line_size = mb_strlen($current_line);
			if ($current_line_size + mb_strlen($words[$i]) + 1 <= $line_size) {
                // Слово вмещается в текущую строку
				$current_line .= $words[$i]." ";
			} else {
                // Слово не вмещается. Завершаем строку
				if ($current_line_size > 0) {
					$output[] = $current_line;
				}

				if (mb_strlen($words[$i]) + 1 > $line_size) {
                    // Строка после переноса не вмещается
					while (mb_strlen($words[$i]) > $line_size) {
						$output[] = mb_substr($words[$i], 0, $line_size);
						$words[$i] = mb_substr($words[$i], $line_size);
					}
					$current_line = $words[$i]." ";
				} else {
                    // Добавляем новую строку, сразу помещаем на неё слово
					$current_line = $words[$i]." ";
				}
			}
		}

		// Добавление оставшихся данных
		$output[] = $current_line;

		return $output;
	}

    // Вычисляет высоту и ширину текста без padding в пикселях
    // $lines - массив строк яйчейки
    // $line_height - высота строки в пикселях
    // $line_spacing - высота между строками в пикселях
    // $font - путь к шрифту TrueType
    // $font_size - размер шрифта в типографских пунктах
	private static function getTextSize
    (
        array $lines,
        int $line_height,
        int $line_spacing,
        string $font,
        float $font_size
    ) : array {
		$width = 0;
        $height = 0;

		foreach ($lines as $line) {
			$box = imagettfbbox($font_size, 0, $font, $line);
            
            // Размеры яйчейки принимает такое значение, чтобы
            // вместились все строки по ширине и высоте
			$width = max($width, abs($box[2] - $box[0]));
            $height += $line_height + $line_spacing;
		}

        // В последней строке не нужно добавлять расстояния между 
        // строками
        $height -= $line_spacing;

        return [$width, $height];
	}

    // Отрисовывает обычный задний фон строки таблицы
    // $im - объект изображения
    // $colors - цвета, выделенные с помощью imagecollorallocate
    // $row_data - строка таблицы
    // $is_even - чётна ли строка
    // $x1, $x2, $y1, $y2 - координаты отрисовки фона
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
    ) : void
    {
        if ($is_even) {
            $color = $colors['body_bg_even'];
        } else {
            $color = $colors['body_bg_odd'];
        }
        imagefilledrectangle($im, $x1, $y1, $x2, $y2, $color);
    }
}
