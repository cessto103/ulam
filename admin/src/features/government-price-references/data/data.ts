export const sources = [
  { label: 'DA Bantay Presyo', value: 'da_bantay_presyo' },
  { label: 'DTI SRP', value: 'dti_srp' },
] as const

// The API stores `region` as free text (admin-entered, no enum on the backend).
// This is the standard list of PH administrative regions, used only to power the
// table's region filter dropdown — it may not perfectly match values already in the DB.
export const regions = [
  { label: 'NCR', value: 'NCR' },
  { label: 'CAR', value: 'CAR' },
  { label: 'Region I', value: 'Region I' },
  { label: 'Region II', value: 'Region II' },
  { label: 'Region III', value: 'Region III' },
  { label: 'Region IV-A', value: 'Region IV-A' },
  { label: 'MIMAROPA', value: 'MIMAROPA' },
  { label: 'Region V', value: 'Region V' },
  { label: 'Region VI', value: 'Region VI' },
  { label: 'Region VII', value: 'Region VII' },
  { label: 'Region VIII', value: 'Region VIII' },
  { label: 'Region IX', value: 'Region IX' },
  { label: 'Region X', value: 'Region X' },
  { label: 'Region XI', value: 'Region XI' },
  { label: 'Region XII', value: 'Region XII' },
  { label: 'Region XIII', value: 'Region XIII' },
  { label: 'BARMM', value: 'BARMM' },
] as const
