/** Shows an ApiError: the Spanish message from the API and, if any, the field details. */
export default function ErrorMessage({ error }) {
  if (!error) return null;
  return (
    <div className="alert alert-error" role="alert">
      <p>{error.message}</p>
      {error.details?.length > 0 && (
        <ul>
          {error.details.map((detail, index) => (
            <li key={index}>
              {detail.field ? <code>{detail.field}</code> : null} {detail.issue}
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
