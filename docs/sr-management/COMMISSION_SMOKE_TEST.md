# Sales Commission & Affiliate Module — Smoke Test

## Test 1: Salesman Commission

1. Create an active salesman commission rule.
2. Create a POS order as pending/quotation.
3. Confirm no commission is generated.
4. Change status to invoiced.
5. Confirm commission entry is generated.
6. Confirm product order commission total is updated.
7. Confirm order profit cost summary is updated.

## Test 2: Affiliate Commission

1. Create affiliate code: `TESTAFF`.
2. Create active affiliate commission rule.
3. Add affiliate code in POS/e-commerce order edit.
4. Confirm order.
5. Confirm affiliate commission entry is generated.

## Test 3: Rollback Safety

1. Take an invoiced order with commission.
2. Edit order back to pending or cancelled state.
3. Confirm unpaid commission is reversed.
4. Confirm order profit cost is reversed.

## Test 4: Paid Commission Return Case

1. Approve commission entry.
2. Create settlement.
3. Mark settlement paid.
4. Return/cancel the order.
5. Confirm entry becomes review/adjustment flow, not deleted.

## Test 5: Settlement

1. Approve multiple commission entries.
2. Generate settlement for date range.
3. Approve settlement.
4. Mark partial payment.
5. Confirm paid and due amounts are correct.

## Test 6: Report

1. Open Commission Report.
2. Filter by date range.
3. Check earned, approved, paid and due cards.
4. Export CSV.
