# Fixed Asset Stage 06 — Controller & Service Polish

This patch moves core business writes out of controllers and into services.

## Added

- `FixedAssetLifecycleService`
- `FixedAssetAssignmentService`
- `FixedAssetTransferService`
- `FixedAssetAssignmentRequest`
- `FixedAssetAssignmentReturnRequest`
- `FixedAssetTransferRequest`
- `FixedAssetTransferReceiveRequest`

## Updated Controllers

- `FixedAssetController`
- `FixedAssetAssignmentController`
- `FixedAssetTransferController`

## Notes

- No migration or database changes.
- Warehouse remains mandatory for asset create and assignment.
- Controller code is now thinner and safer for production extension.
- Accounting capitalization flow remains in asset lifecycle service.
