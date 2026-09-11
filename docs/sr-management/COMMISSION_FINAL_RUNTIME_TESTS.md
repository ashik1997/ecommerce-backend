# Final Runtime Test Checklist

## Admin setup

- [ ] `php artisan migrate` successful
- [ ] `php artisan db:seed --class=CommissionPermissionRoutesSeeder` successful
- [ ] `php artisan db:seed --class=CommissionAccountingSeeder` successful
- [ ] `php artisan commission:diagnose` shows all tables OK
- [ ] SR Management sidebar shows Commission menus

## Salesman commission

- [ ] Create active salesman rule
- [ ] Create POS order with salesman
- [ ] Keep order pending/quotation: no commission
- [ ] Change to invoiced/delivered: commission generated
- [ ] Edit confirmed order: old commission reversed/recalculated safely
- [ ] Cancel/rollback order: commission reversed

## Affiliate commission

- [ ] Create affiliate partner with unique code
- [ ] Validate affiliate code from order form
- [ ] Create eCommerce/manual order with affiliate code
- [ ] Confirm order: affiliate commission generated
- [ ] Invalid affiliate code: no affiliate commission

## Settlement

- [ ] Approve pending commission entries
- [ ] Generate settlement draft
- [ ] Approve settlement: payable/expense accounting entry created
- [ ] Mark settlement paid: payment accounting entry created
- [ ] Partial payment updates due correctly
- [ ] Paid commission reversal creates next-settlement deduction

## Reports

- [ ] Commission Report cards match entries and settlements
- [ ] Commission Ledger shows earned, paid, deduction, closing due
- [ ] CSV export works
- [ ] Order profit summary shows commission cost impact
