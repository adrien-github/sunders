CREATE TABLE statistics (
  id BIGINT REFERENCES position ON DELETE CASCADE,
  ts VARCHAR(20),
  year SMALLINT,
  month TINYINT,
  day TINYINT,
  t TIME,
  week TINYINT,
  country VARCHAR(2),
  version INT,
  PRIMARY KEY (id)
);

INSERT INTO statistics (
	id,
	ts,
	version)
SELECT
  t1.id,
  t1.v,
  t2.v
FROM tag AS t1
INNER JOIN tag AS t2
ON t2.id = t1.id AND t2.k = 'version'
WHERE t1.k = 'timestamp'
