#!/usr/bin/env python3
import argparse
import json
import math
import random
from datetime import datetime


def mae(y_true, y_pred):
    if not y_true:
        return 0.0
    return sum(abs(a - b) for a, b in zip(y_true, y_pred)) / float(len(y_true))


def rmse(y_true, y_pred):
    if not y_true:
        return 0.0
    mse = sum((a - b) ** 2 for a, b in zip(y_true, y_pred)) / float(len(y_true))
    return math.sqrt(mse)


def standardize_rows(rows, feature_names, means, stds):
    out = []
    for row in rows:
        vector = []
        for name in feature_names:
            raw = float(row.get(name, 0.0))
            mean = means[name]
            std = stds[name] if stds[name] > 0 else 1.0
            vector.append((raw - mean) / std)
        out.append(vector)
    return out


def fit_linear_sgd(x_train, y_train, epochs=350, lr=0.03, l2=1e-4, seed=42):
    random.seed(seed)
    if not x_train:
        return [], 0.0

    features = len(x_train[0])
    weights = [0.0] * features
    bias = sum(y_train) / float(len(y_train))

    idxs = list(range(len(x_train)))
    step = lr

    for _ in range(epochs):
        random.shuffle(idxs)
        for i in idxs:
            xi = x_train[i]
            yi = y_train[i]
            pred = bias
            for j in range(features):
                pred += weights[j] * xi[j]

            err = pred - yi
            for j in range(features):
                grad = (err * xi[j]) + (l2 * weights[j])
                weights[j] -= step * grad
            bias -= step * err

        step *= 0.995

    return weights, bias


def predict_matrix(x_rows, weights, bias):
    preds = []
    for xi in x_rows:
        val = bias
        for w, x in zip(weights, xi):
            val += w * x
        preds.append(max(0.0, val))
    return preds


def main():
    parser = argparse.ArgumentParser(description="Train stock forecast AI model (linear SGD).")
    parser.add_argument("--input", required=True, help="Input JSON dataset path.")
    parser.add_argument("--output", required=True, help="Output model JSON path.")
    args = parser.parse_args()

    with open(args.input, "r", encoding="utf-8") as f:
        rows = json.load(f)

    if not isinstance(rows, list) or len(rows) < 50:
        raise SystemExit("Not enough training rows (min 50).")

    feature_names = [
        "last_1d",
        "last_3d",
        "last_7d",
        "prev_7d",
        "last_14d",
        "trend_7d",
        "dow",
        "month",
    ]

    split = int(len(rows) * 0.8)
    if split < 30:
        split = len(rows) - 10
    train_rows = rows[:split]
    test_rows = rows[split:] if split < len(rows) else rows[-10:]

    means = {}
    stds = {}
    for name in feature_names:
        values = [float(r.get(name, 0.0)) for r in train_rows]
        mean = sum(values) / float(len(values))
        var = sum((v - mean) ** 2 for v in values) / float(len(values))
        means[name] = mean
        stds[name] = math.sqrt(var) if var > 1e-12 else 1.0

    x_train = standardize_rows(train_rows, feature_names, means, stds)
    y_train = [float(r.get("target", 0.0)) for r in train_rows]
    x_test = standardize_rows(test_rows, feature_names, means, stds)
    y_test = [float(r.get("target", 0.0)) for r in test_rows]

    weights, bias = fit_linear_sgd(x_train, y_train)
    pred_train = predict_matrix(x_train, weights, bias)
    pred_test = predict_matrix(x_test, weights, bias)

    model = {
        "version": 1,
        "algorithm": "linear_sgd",
        "trained_at": datetime.utcnow().isoformat() + "Z",
        "feature_names": feature_names,
        "feature_means": means,
        "feature_stds": stds,
        "weights": {name: w for name, w in zip(feature_names, weights)},
        "bias": bias,
        "metrics": {
            "rows_total": len(rows),
            "rows_train": len(train_rows),
            "rows_test": len(test_rows),
            "mae_train": mae(y_train, pred_train),
            "rmse_train": rmse(y_train, pred_train),
            "mae_test": mae(y_test, pred_test),
            "rmse_test": rmse(y_test, pred_test),
        },
    }

    with open(args.output, "w", encoding="utf-8") as f:
        json.dump(model, f, ensure_ascii=True, indent=2)

    print(
        "MODEL_TRAINED "
        f"rows={model['metrics']['rows_total']} "
        f"mae_test={model['metrics']['mae_test']:.3f} "
        f"rmse_test={model['metrics']['rmse_test']:.3f} "
        f"output={args.output}"
    )


if __name__ == "__main__":
    main()

