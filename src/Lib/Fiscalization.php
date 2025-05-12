<?php
namespace Avangard\Lib;

class Fiscalization
{
    /**
     * Validate order items and check order amount
     *
     * @param array $params
     * - amount (number) сумма к оплате, в копейках. Если не передан, то проверка суммы всех товаров с общей суммой заказа не будет проводиться
     * - items (array, require) список товаров
     *      Имеет следующий вид:
     *          [
     *              [
     *                  num (number, require) - позиция в чеке
     *                  name (string, require) - наименование товара
     *                  quantity (number, require) - количество товара
     *                  price (number, require) - цена за единицу товара в рублях
     *                  fullPrice (number, require) - итоговая цена позиции
     *                  isService (0/1) - значение заполняется, если позиция в чеке является доставкой или иной услугой (необходимо для выставления правильного объекта расчёта)
     *              ],
     *              ...
     *          ]
     *      Сумма всех товаров в ORDER_ITEMS должна равняться полю AMOUNT / 100
     * @return void
     */
    public static function checkOrderItems($params)
    {
        $orderItemsReqParams = [
            'name' => 'STRING',
            'quantity' => 'NUMERIC',
            'price' => 'NUMERIC',
            'fullPrice' => 'NUMERIC',
        ];

        $orderItemsTotal = 0;
        foreach ($params['items'] as $item) {
            foreach ($orderItemsReqParams as $key => $type) {
                if (empty($item[$key])) {
                    throw new \InvalidArgumentException(
                        'error in validation: order item key ' . $key . ' not found'
                    );
                }

                if ((float)$item['price'] * (float)$item['quantity'] != (float)$item['fullPrice']) {
                    throw new \InvalidArgumentException(
                        'error in validation: item "' . $item['name'] . '" price * quantity not equal to item full price'
                    );
                }

                if (isset($item['isService']) && !in_array($item['isService'], [0, 1])) {
                    throw new \InvalidArgumentException(
                        'error in validation: order item key isService should be 0 or 1'
                    );
                }
            }

            $orderItemsTotal += $item['fullPrice'];
        }

        if (!empty($params['amount'])) {
            $orderAmount = $params['amount'] / 100;
            if ($orderItemsTotal != $orderAmount) {
                throw new \InvalidArgumentException(
                    "error in validation: total items price $orderItemsTotal not equal to order amount $orderAmount"
                );
            }
        }
    }

    public static function prepareOrderItems($items)
    {
        return json_encode($items);
    }
}
