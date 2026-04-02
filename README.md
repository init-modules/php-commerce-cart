# init/commerce-cart

Commerce cart foundation package for visitor sessions and authenticated actors.

## Что реализовано

- `commerce_carts` и `commerce_cart_items` со snapshot-ценами и snapshot-каталогом на уровне строки.
- active cart per actor через `active_actor_key`, совместимый с `init/visitor-session`.
- actions:
  - `ResolveActiveCart`
  - `AddCatalogItemToCart`
  - `MergeActorCarts`
  - `UpdateCartItemQuantity`
  - `RemoveCartItem`
- API current cart:
  - `GET /api/commerce/cart/v1/current`
  - `POST /api/commerce/cart/v1/current/items`
  - `PATCH /api/commerce/cart/v1/current/items/{item}`
  - `DELETE /api/commerce/cart/v1/current/items/{item}`
- Filament resource `Корзины` с relation manager для cart items.

## Установка

```bash
composer require init/commerce-cart
```

## Использование

- Для visitor actors передавай `X-Visitor-Session` header из `init/visitor-session`.
- Для authenticated actors пакет использует текущего `Request::user()`.
- Для добавления позиции нужен `catalog_item_id` и опционально `catalog_item_type`.

## Структура

- path: `commerce-foundation/commerce-cart`
- actions:
- `ResolveActiveCart`
- `AddCatalogItemToCart`
- `MergeActorCarts`
- `UpdateCartItemQuantity`
- `RemoveCartItem`

## Разработка

- Demo seeders регистрируются только вне production.
- package tests запускаются через `make setup && make test`.
- package checks лежат в `tests/Feature/`.
- app-level Filament integration checks добавлены в `laravel/tests/Feature/Commerce/CommerceAdminIntegrationTest.php`.
